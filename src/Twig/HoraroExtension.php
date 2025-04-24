<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HoraroExtension extends AbstractExtension
{
    public function __construct(protected readonly TwigUtils $utils)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'obscurify',
                fn (string $id, string $entityType) => $this->obscurify($id, $entityType),
            ),
            new TwigFilter(
                'shorten',
                fn (string $string, int $maxlen) => $this->shorten($string, $maxlen),
            ),
        ];
    }

    private function shorten(string $string, int $maxlen): string
    {
        return $this->utils->shorten($string, $maxlen);
    }

    private function obscurify(string $id, string $entityType): string
    {
        return $id;
    }
}
