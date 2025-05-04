<?php

namespace App\Horaro\Transformer;

use App\Horaro\Service\ObscurityCodecService;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class BaseTransformer extends TransformerAbstract
{
    protected string $baseUri;

    public function __construct(
        protected readonly RequestStack $requestStack,
        protected readonly ObscurityCodecService $obscurityCodec,
    )
    {
        $this->baseUri = $requestStack->getCurrentRequest()->getUriForPath('');
    }

    protected function encodeID(int $id, string $entityType = null): string
    {
        return $this->obscurityCodec->encode($id, $entityType);
    }

    protected function decodeID(string $hash, string $entityType = null): ?int
    {
        return $this->obscurityCodec->decode($hash, $entityType);
    }

    protected function base(): string
    {
        return $this->baseUri;
    }

    protected function url($relative): string
    {
        return $this->base().'/-/api'.$relative;
    }
}
