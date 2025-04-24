<?php

namespace App\Horaro\Library\ObscurityCodec;

use App\Horaro\Library\ObscurityCodec;

class Debug implements ObscurityCodec
{
    public function encode($id, $entityType = null): string
    {
        return (string) $id;
    }

    public function decode($hash, $entityType = null): ?int
    {
        if (!ctype_digit((string) $hash)) {
            return null;
        }

        $id = (int) $hash;

        return $id ?: null;
    }
}
