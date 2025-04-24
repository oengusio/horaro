<?php

namespace App\Horaro\Library\ObscurityCodec;

use App\Horaro\Library\ObscurityCodec;
use Jenssegers\Optimus\Optimus as BaseOptimus;

class Optimus implements ObscurityCodec
{
    public function __construct(protected readonly BaseOptimus $optimus) {}

    public function encode(int $id, ?string $entityType = null): string
    {
        $typeHash = strtolower(sha1($entityType));

        $encoded = $this->optimus->encode($id);
        $hex     = sprintf('%08x', $encoded);
        $base36  = sprintf('%06s', strtolower(base_convert($encoded, 10, 36)));
        $base36  = strrev($base36);

        $h = str_split($hex, 2);       // [AA, BB, CC, DD]
        $b = str_split($base36, 2);    // [ZY, XW, VU]
        $t = str_split($typeHash, 2);  // [H1, H2, ..., H19, H20]

        // aa,h1,zy,bb,xw,cc,vu,h20,dd
        return $h[0].$t[0].$b[0].$h[1].$b[1].$h[2].$b[2].$t[19].$h[3];
    }

    public function decode(string $hash, ?string $entityType = null): ?int
    {
        $s = (string) $hash;

        if (!ctype_alnum($s) || strlen($s) !== 18) {
            return null;
        }

        $parts = str_split($s, 2); // [AA, H1, ZY, BB, XW, CC, VU, H20, DD]

        // check the entity hash

        $typeHash  = strtolower(sha1($entityType));
        $typeParts = str_split($typeHash, 2);

        if ($typeParts[0] !== $parts[1] || $typeParts[19] !== $parts[7]) {
            return null;
        }

        // check the ID itself

        $hex    = $parts[0].$parts[3].$parts[5].$parts[8];
        $base36 = strtolower(strrev($parts[2].$parts[4].$parts[6]));

        $dec1 = @hexdec($hex);
        $dec2 = (int) @base_convert($base36, 36, 10);

        if ($dec1 <= 0 || $dec2 <= 0 || $dec1 !== $dec2) {
            return null;
        }

        return $this->optimus->decode($dec1);
    }

}
