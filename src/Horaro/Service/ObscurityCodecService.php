<?php

namespace App\Horaro\Service;

use App\Horaro\Library\ObscurityCodec;
use Jenssegers\Optimus\Optimus;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class ObscurityCodecService implements ObscurityCodec
{
    private ObscurityCodec $implementation;

    public function __construct(ContainerBagInterface $params)
    {
        if ($params->get('debug')) {
            $this->implementation = new ObscurityCodec\Debug();
        } else {
            $config = $params->get('optimus');
            $optimus = new Optimus($config['prime'], $config['inverse'], $config['random']);

            $this->implementation = new ObscurityCodec\Optimus($optimus);
        }
    }

    public function encode(int $id, ?string $entityType = null): string
    {
        return $this->implementation->encode($id, $entityType);
    }

    public function decode(string $hash, ?string $entityType = null): ?int
    {
        return $this->implementation->decode($hash, $entityType);
    }
}
