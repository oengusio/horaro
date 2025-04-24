<?php

namespace App\Horaro\Library\ObscurityCodec;

use App\Horaro\Library\ObscurityCodec;
use Hashids\Hashids as VndHashids; // TODO: This lib was not in the package json anymore?

// TODO: probably delete this class
class Hashids implements ObscurityCodec
{
    protected string $secret;
    protected int $minLength;
    protected array $hashers;

    public function __construct(string $secret, int $minLength) {
        $this->secret    = $secret;
        $this->minLength = $minLength;
        $this->hashers   = [];
    }

    public function encode(int $id, ?string $entityType = null): string
    {
        return $this->buildHasher($entityType)->encode($id);
    }

    public function decode(string $hash, ?string $entityType = null): ?int
    {
        $decoded = $this->buildHasher($entityType)->decode($hash);

        if (!is_array($decoded) || empty($decoded)) {
            return null;
        }

        return reset($decoded);
    }

    protected function buildHasher(?string $entityType): VndHashids {
        $secret = $this->secret;

        if ($entityType) {
            $secret .= ' / '.$entityType;
        }

        // not all characters in the secret are relevant, so by hashing we make sure
        // the added entity type has an actual effect on the hashing later
        $secret = md5($secret);

        if (!isset($this->hashers[$secret])) {
            $this->hashers[$secret] = new VndHashids($secret, $this->minLength, 'abcdefghijklmnopqrstuvwxyz1234567890');
        }

        return $this->hashers[$secret];
    }
}
