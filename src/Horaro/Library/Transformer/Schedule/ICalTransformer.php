<?php

namespace App\Horaro\Library\Transformer\Schedule;

use App\Entity\Schedule;
use App\Entity\ScheduleItem;
use App\Horaro\Library\Transformer\Schedule\BaseTransformer;

class ICalTransformer extends BaseTransformer
{
    const DATE_FORMAT = 'Ymd\THis\Z';

    private $md = null; // TODO: markdown

    function getContentType(): string
    {
        return 'text/calendar; charset=UTF-8';
    }

    function getFileExtension(): string
    {
        return 'ics';
    }

    function transform(Schedule $schedule, bool $public = false, bool $withHiddenColumns = false): string
    {
        $utc         = new \DateTimeZone('UTC');
        $now         = new \DateTime('now', $utc);
        $tz          = $schedule->getTimezone();
        $scheduled   = $schedule->getUTCStart();
        $columns     = $this->getEffectiveColumns($schedule, $withHiddenColumns);
        $columnNames = [];
        $columnIDs   = [];

        foreach ($columns as $col) {
            $columnIDs[] = $col->getId();
            $columnNames[$col->getId()] = $col->getName();
        }

        $summaryCol   = reset($columnIDs);
        $extendedCols = array_slice($columnIDs, 1);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:'.$this->getProductID(),
            'CALSCALE:GREGORIAN',
            $this->getString($schedule->getEvent()->getName().' '.$schedule->getName(), 'X-WR-CALNAME'),
            'X-PUBLISHED-TTL:PT15M'
        ];

        foreach ($schedule->getScheduledItems() as $item) {
            $extra       = $item->getExtra();
            $summary     = isset($extra[$summaryCol]) ? $extra[$summaryCol] : '(unnamed)';
            $description = [];

            if ($this->md) {
                $summary = $this->md->convertInline($summary);
                $summary = htmlspecialchars_decode(strip_tags($summary), ENT_QUOTES);
            }

            foreach ($extendedCols as $extCol) {
                if (isset($extra[$extCol])) {
                    $colName       = $columnNames[$extCol];
                    $colContent    = $extra[$extCol];

                    if ($this->md) {
                        $colContent = $this->md->convertInline($colContent);
                        $colContent = htmlspecialchars_decode(strip_tags($colContent), ENT_QUOTES);
                    }

                    $description[] = sprintf('%s: %s', $colName, $colContent);
                }
            }

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'DTSTART:'.$item->getScheduled($utc)->format(self::DATE_FORMAT);
            $lines[] = 'DTEND:'.$item->getScheduledEnd($utc)->format(self::DATE_FORMAT);
            $lines[] = 'DTSTAMP:'.$now->format(self::DATE_FORMAT);
            $lines[] = 'UID:'.$this->generateUID($item);
            $lines[] = $this->getString($summary, 'SUMMARY');

            if (!empty($description)) {
                $lines[] = $this->getString(implode("\n", $description), 'DESCRIPTION');
            }

            $lines[] = 'CLASS:PUBLIC';
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';
        $result  = implode("\r\n", $lines)."\r\n";

        return $result;
    }

    public function generateUID(ScheduleItem $item): string
    {
        $event    = $item->getSchedule()->getEvent()->getSlug();
        $schedule = $item->getSchedule()->getSlug();
        $item     = $this->encodeID($item->getId(), 'schedule.item');

        return sprintf('%s_%s_%s@%s', $event, $schedule, $item, $this->getHost());
    }

    protected function getProductID(): string
    {
        // http://www.xfront.com/schematron/formal-public-identifier.html
        return '-//kabukiman//horaro//EN';
    }

    public function getString($str, $property): string
    {
        // RFC says (3.1): "Lines of text SHOULD NOT be longer than 75 **octets**, excluding the line break."
        // Plus, we don't want to cut Unicode chars in half, so until someone finds
        // a really cool algorithm to do so, we just go character by character and
        // count the bytes (character != byte).

        $str   = addcslashes($str, ',;\\'."\n");
        $total = strtoupper($property).':'.$str;

        // simple case, do nothing
        if (mb_strlen($total, '8bit') <= 74) return $total;

        $result = '';
        $len    = 0;

        while (mb_strlen($total) !== 0) {
            $char   = mb_substr($total, 0, 1);
            $octets = mb_strlen($char, '8bit');

            if ($len+$octets > 74) {
                $result .= "\r\n $char";
                $len     = $octets + 1; // len of char + the leading space
            }
            else {
                $result .= $char;
                $len    += $octets;
            }

            // cut off the first character
            $total = mb_substr($total, 1);
        }

        return $result;
    }

    private function getHost(): string
    {
        return $this->requestStack->getCurrentRequest()->getHost();
    }
}
