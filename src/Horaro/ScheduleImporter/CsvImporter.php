<?php

namespace App\Horaro\ScheduleImporter;

use App\Entity\Schedule;
use App\Entity\ScheduleColumn;
use App\Entity\ScheduleItem;
use League\Csv\Info;
use League\Csv\Reader;

class CsvImporter extends BaseImporter
{
    public function import(string $filePath, Schedule $schedule, bool $ignoreErrors, bool $updateMetadata): array
    {
        try {
            $csv = Reader::createFromPath($filePath);
        }
        catch (\Exception $e) {
            throw new \Exception('This file does not look like CSV at all.', null, $e);
        }

        $csv->setEnclosure('"');
        $csv->setEscape('\\');

        $probe = Info::getDelimiterStats($csv, [',', ';', "\t", '~'], 10);
        if (empty($probe)) {
            throw new \Exception('Could not determine the column separator. Please use comma (,), semicolon (;) or tab (\\t).');
        }

        arsort($probe);
        $csv->setDelimiter(key($probe));

        // check the header row
        $headers = $csv->nth(0);

        if (empty($headers)) {
            throw new \Exception('No header row found.');
        }

        // scan for columns containing the item length
        $lengthColumns     = [];
        $keepLengthColumns = false;

        foreach ($headers as $pos => $col) {
            if (preg_match('/^(length|estimated?|minutes|duration|run time|.*?\bsetup\b.*?)$/i', $col)) {
                $lengthColumns[$pos] = $col;
            }
        }

        if (empty($lengthColumns)) {
            throw new \RuntimeException('None of the columns look like they contain the length. I expect to find at least one column named "length", "estimate" or "duration".');
        }
        elseif (count($lengthColumns) === 1) {
            $this->log('ok', 'Found one column that probably contains the length: #'.(key($lengthColumns) + 1).', "'.reset($lengthColumns).'"');
        }
        else {
            $this->log('ok', 'Found '.count($lengthColumns).' columns that probably contain the length: '.implode(', ', $lengthColumns).' -- going to add their times together to get the actual length.');
            $keepLengthColumns = true;
        }

        // import columns
        $pos     = 1;
        $columns = [];

        foreach ($headers as $idx => $col) {
            // do not interpret length columns as data columns, unless we want to keep them
            if (isset($lengthColumns[$idx]) && !$keepLengthColumns) continue;

            if ($pos <= 10) {
                $column = new ScheduleColumn();
                $column->setName(mb_substr($col, 0, 128))
                       ->setPosition($pos)
                       ->setHidden($col === Schedule::OPTION_COLUMN_NAME);

                $columns[] = $column;
                $this->log('ok', 'Imported column #'.$pos.', "'.$col.'"');
            }
            else {
                $this->log('warn', 'Ignoring column #'.$pos.' ("'.$col.'").');
            }

            $pos++;
        }

        // and now we finally read through the items and import them
        $pos      = 1;
        $items    = [];
        $tmpDate  = new \DateTime('@0');
        $maxItems = $schedule->getMaxItems();

        foreach ($csv as $rowIdx => $row) {
            if ($rowIdx === 0) continue; // skip header

            $seconds = 0;

            // check for a valid length
            foreach (array_keys($lengthColumns) as $colIdx) {
                if (!isset($row[$colIdx])) continue;

                $value = $row[$colIdx];

                if (preg_match('/^\d+(:\d\d)?(:\d\d)?$/', $value)) {
                    $parts = explode(':', $value);

                    if (count($parts) === 1) {
                        $seconds += ($parts[0] * 60);
                    }
                    elseif (count($parts) === 2) {
                        $seconds += ($parts[0] * 3600) + ($parts[1] * 60);
                    }
                    elseif (count($parts) === 3) {
                        $seconds += ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
                    }
                }
                elseif (preg_match('/^P[0-9DWTHMS]$/', $value)) {
                    try {
                        $length = new \DateInterval($value);

                        // convert DateInterval into number of seconds
                        $tmp = clone $tmpDate;
                        $tmp->add($length);

                        $seconds = (int) $tmp->format('U');
                    }
                    catch (\Exception $e) {
                        $this->log('warn', 'Value at '.($rowIdx+1).'/'.$colIdx.' looked like an ISO time duration, but failed to decode as one.');
                    }
                }
            }

            if ($seconds < 1) {
                $this->log('error', 'Row '.($rowIdx+1).' did not contain something resembling a time length. Cannot import it.');
                if ($ignoreErrors) continue;
                return $this->returnLog();
            }

            if ($seconds > 7*24*3600) {
                $this->log('error', 'Length of row #'.($rowIdx+1).' is too large (maximum is 7 days). Cannot import row.');
                if ($ignoreErrors) continue;
                return $this->returnLog();
            }

            // collect additional data
            $extra = [];

            foreach ($row as $colIdx => $value) {
                $isLengthCol = isset($lengthColumns[$colIdx]);

                if (count($extra) < 10 && (!$isLengthCol || $keepLengthColumns)) {
                    $extra[] = mb_substr($value, 0, 512);
                }
            }

            // now we can create the item. Since we don't have the column IDs yet, we insert a plain
            // array and take care of fixing that later.
            $item = new ScheduleItem();
            $item->setPosition($pos)->setLengthInSeconds($seconds);

            // avoid the overhead of setExtra()'s json_encoding
            $item->tmpExtra = $extra;

            $items[] = $item;
            $this->log('ok', 'Imported row #'.($rowIdx+1).'.');

            $pos++;

            if ($pos > $maxItems) {
                $this->log('warn', 'Ignoring any further rows.');
                break;
            }
        }

        // Now we have the columns and items, but nothing is persisted yet. We will now replace the
        // columns with the new ones, so they get their ID assigned.
        $columnIDs = $this->replaceColumns($schedule, $columns);

        // Now we can fix the extra data on the items and insert the column IDs.
        $this->replaceItems($schedule, $items, $columnIDs);

        // mark the schedule as updated
        $schedule->touch();

        $this->flush();

        return $this->returnLog();
    }

}
