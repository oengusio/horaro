<?php

namespace App\Horaro\Library;

interface ObscurityCodec
{
    public function encode(int $id, ?string $entityType = null): string;
    public function decode(string $hash, ?string $entityType = null): ?int;
}
