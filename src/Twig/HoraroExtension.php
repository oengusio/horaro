<?php

namespace App\Twig;

use App\Horaro\Service\ObscurityCodecService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HoraroExtension extends AbstractExtension
{
    public function __construct(
        private readonly TwigUtils $utils,
        private readonly ObscurityCodecService $obscurityCodec,
    )
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'obscurify',
                fn (int $id, string $entityType) => $this->obscurify($id, $entityType),
            ),
            new TwigFilter(
                'shorten',
                fn (?string $string, int $maxlen) => $this->shorten($string, $maxlen),
            ),
        ];
    }

    private function shorten(?string $string, int $maxlen): string
    {
        return $this->utils->shorten($string, $maxlen);
    }

    private function obscurify(int $id, string $entityType): string
    {
        return $this->obscurityCodec->encode($id, $entityType);
    }
}
