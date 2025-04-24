<?php

namespace App\Horaro\Library\Transformer\Schedule;

use App\Entity\Schedule;
use App\Horaro\Library\Transformer\Schedule\BaseTransformer;

class CsvTransformer extends BaseTransformer
{
    const DATE_FORMAT = 'r';

    function getContentType(): string
    {
        return 'text/csv; charset=UTF-8';
    }

    function getFileExtension(): string
    {
        return 'csv';
    }

    function transform(Schedule $schedule, bool $public = false, bool $withHiddenColumns = false): string
    {
        $rows  = [];
        $cols  = $this->getEffectiveColumns($schedule, $withHiddenColumns);
        $toCSV = fn ($val) => '"'.addcslashes($val, '"').'"';

        $header = [$toCSV('Scheduled'), $toCSV('Length')];

        foreach ($cols as $col) {
            $prefix = $col->isHidden() ? 'hidden:' : '';

            $header[] = $toCSV($prefix.$col->getName());
        }

        $rows[] = implode(';', $header);

        foreach ($schedule->getScheduledItems() as $item) {
            $extra = $item->getExtra();
            $row   = [
                'scheduled' => $toCSV($item->getScheduled()->format(self::DATE_FORMAT)),
                'length'    => $toCSV($item->getLength()->format('H:i:s'))
            ];

            foreach ($cols as $col) {
                $colID = $col->getId();
                $row[] = isset($extra[$colID]) ? $toCSV($extra[$colID]) : '';
            }

            $rows[] = implode(';', $row);
        }

        return implode("\n", $rows);
    }
}
