<?php

namespace App\Horaro\Library\Transformer\Schedule;

use App\Entity\Schedule;
use App\Entity\ScheduleColumn;
use App\Entity\ScheduleItem;
use App\Horaro\Library\ObscurityCodec;
use App\Horaro\Library\Transformer\Schedule\BaseTransformer;

class JsonTransformer extends BaseTransformer
{
    const DATE_FORMAT_TZ  = 'Y-m-d\TH:i:sP';
    const DATE_FORMAT_UTC = 'Y-m-d\TH:i:s\Z';

    protected $hint = true;

    public function getContentType(): string
    {
        return 'application/json; charset=UTF-8';
    }

    public function getFileExtension(): string
    {
        return 'json';
    }

    function transform(Schedule $schedule, bool $public = false, bool $withHiddenColumns = false): string
    {
        $cols    = $this->getEffectiveColumns($schedule, $withHiddenColumns);
        $columns = [];
        $hidden  = [];

        foreach ($cols as $col) {
            $columns[] = $col->getName();

            if ($col->isHidden()) {
                $hidden[] = $col->getName();
            }
        }

        // make it possible to hide the options by specifying the ?hiddenkey secret
        $optionsCol = $withHiddenColumns ? $schedule->getOptionsColumn() : null;

        $items = [];
        foreach ($schedule->getScheduledItems() as $item) {
            $items[] = $this->transformItem($item, $cols, $optionsCol);
        }

        $event = $schedule->getEvent();
        $start = $schedule->getLocalStart();

        $data = [
            'meta' => [
                'exported' => gmdate(self::DATE_FORMAT_UTC),
                'hint'     => 'Use ?callback=yourcallback to use this document via JSONP.',
                'api'      => 'This is a living document and may change over time. For a stable, well-defined output, use the API instead.',
                'api-link' => '/-/api/v1/schedules/'.$this->encodeID($schedule->getId(), ObscurityCodec::SCHEDULE)
            ],
            'schedule' => [
                'id'          => $this->encodeID($schedule->getId(), ObscurityCodec::SCHEDULE),
                'name'        => $schedule->getName(),
                'slug'        => $schedule->getSlug(),
                'timezone'    => $schedule->getTimezone(),
                'start'       => $start->format(self::DATE_FORMAT_TZ),
                'start_t'     => (int) $start->format('U'),
                'website'     => $schedule->getWebsite() ?: $event->getWebsite(),
                'twitter'     => $schedule->getTwitter() ?: $event->getTwitter(),
                'twitch'      => $schedule->getTwitch() ?: $event->getTwitch(),
                'description' => $schedule->getDescription(),
                'setup'       => $schedule->getSetupTimeISODuration(),
                'setup_t'     => $schedule->getSetupTimeInSeconds(),
                'theme'       => $schedule->getTheme(),
                'secret'      => $schedule->getSecret(),
                'updated'     => $schedule->getUpdatedAt()->format(self::DATE_FORMAT_UTC), // updated is stored as UTC, so it's okay to disregard the sys timezone here and force UTC
                'url'         => sprintf('/%s/%s', $event->getSlug(), $schedule->getSlug()),
                'event'       => [
                    'id'     => $this->encodeID($event->getId(), ObscurityCodec::EVENT),
                    'name'   => $event->getName(),
                    'slug'   => $event->getSlug(),
                    'theme'  => $event->getTheme(),
                    'secret' => $event->getSecret()
                ],
                'hidden_columns' => $hidden,
                'columns'        => $columns,
                'items'          => $items
            ]
        ];

        if (!$schedule->isPublic()) {
            unset($data['meta']['api']);
            unset($data['meta']['api-link']);
        }

        if (!$withHiddenColumns) {
            unset($data['schedule']['hidden_columns']);
        }

        if ($public) {
            unset($data['schedule']['id']);
            unset($data['schedule']['theme']);
            unset($data['schedule']['secret']);
            unset($data['schedule']['event']['id']);
            unset($data['schedule']['event']['theme']);
            unset($data['schedule']['event']['secret']);
        }

        if (!$this->hint || !$public) {
            unset($data['meta']['hint']);
        }

        return json_encode($data, JSON_UNESCAPED_SLASHES);
    }

    // the following methods are helpers for the API and do not return JSON, but arrays

    public function transformTicker(Schedule $schedule, array $ticker, $public = false, $withHiddenColumns = false): array {
        $cols    = $this->getEffectiveColumns($schedule, $withHiddenColumns);
        $columns = [];
        $hidden  = [];

        foreach ($cols as $col) {
            $columns[] = $col->getName();

            if ($col->isHidden()) {
                $hidden[] = $col->getName();
            }
        }

        $event = $schedule->getEvent();
        $start = $schedule->getLocalStart();

        // make it possible to hide the options by specifying the ?hiddenkey secret
        $optionsCol = $withHiddenColumns ? $schedule->getOptionsColumn() : null;

        $data = [
            'schedule' => [
                'id'             => $this->encodeID($schedule->getId(), ObscurityCodec::SCHEDULE),
                'name'           => $schedule->getName(),
                'slug'           => $schedule->getSlug(),
                'timezone'       => $schedule->getTimezone(),
                'start'          => $start->format(self::DATE_FORMAT_TZ),
                'start_t'        => (int) $start->format('U'),
                'setup'          => $schedule->getSetupTimeISODuration(),
                'setup_t'        => $schedule->getSetupTimeInSeconds(),
                'updated'        => $schedule->getUpdatedAt()->format(self::DATE_FORMAT_UTC), // updated is stored as UTC, so it's okay to disregard the sys timezone here and force UTC
                'url'            => sprintf('/%s/%s', $event->getSlug(), $schedule->getSlug()),
                'hidden_columns' => $hidden,
                'columns'        => $columns,
            ],
            'ticker' => [
                'previous' => $ticker['prev']   ? $this->transformItem($ticker['prev'],   $cols, $optionsCol) : null,
                'current'  => $ticker['active'] ? $this->transformItem($ticker['active'], $cols, $optionsCol) : null,
                'next'     => $ticker['next']   ? $this->transformItem($ticker['next'],   $cols, $optionsCol) : null,
            ]
        ];

        if (!$withHiddenColumns) {
            unset($data['schedule']['hidden_columns']);
        }

        return $data;
    }

    public function transformItem(ScheduleItem $item, $columns, ?ScheduleColumn $optionsCol = null): array {
        $extra  = $item->getExtra();
        $result = [
            'length'      => $item->getISODuration(),
            'length_t'    => $item->getLengthInSeconds(),
            'scheduled'   => $item->getScheduled()->format(self::DATE_FORMAT_TZ),
            'scheduled_t' => (int) $item->getScheduled()->format('U'),
            'data'        => []
        ];

        foreach ($columns as $col) {
            $colID = $col->getId();

            $result['data'][] = isset($extra[$colID]) ? $extra[$colID] : null;
        }

        if ($optionsCol) {
            $result['options'] = $item->getOptions($optionsCol);
        }

        return $result;
    }
}
