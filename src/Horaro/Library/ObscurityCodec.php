<?php

namespace App\Horaro\Library;

interface ObscurityCodec
{
    const EVENT = 'event'; // TODO: make everything a constant
    CONST SCHEDULE = 'schedule';
    const SCHEDULE_ITEM = 'schedule.item';
    const SCHEDULE_COLUMN = 'schedule.column';

    public function encode(int $id, ?string $entityType = null): string;
    public function decode(string $hash, ?string $entityType = null): ?int;
}
