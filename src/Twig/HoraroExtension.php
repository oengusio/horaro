<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HoraroExtension extends AbstractExtension
{

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
        return $string;
    }

    private function obscurify(string $id, string $entityType): string
    {
        return $id;
    }
}
