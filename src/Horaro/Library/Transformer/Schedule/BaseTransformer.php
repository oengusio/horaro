<?php

namespace App\Horaro\Library\Transformer\Schedule;

use App\Entity\Schedule;
use App\Horaro\Service\ObscurityCodecService;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class BaseTransformer
{
    public function __construct(
        private readonly ObscurityCodecService $codec,
        protected readonly RequestStack $requestStack,
    ) {}

    abstract function getContentType(): string;

    abstract function getFileExtension(): string;

    abstract function transform(Schedule $schedule, bool $public = false, bool $withHiddenColumns = false): string;

    protected function encodeID(int $id, $entityType = null): string
    {
        return $this->codec->encode($id, $entityType);
    }

    protected function decodeID(string $hash, $entityType = null): ?int
    {
        return $this->codec->decode($hash, $entityType);
    }

    protected function getEffectiveColumns(Schedule $schedule, $withHiddenColumns = false): Collection
    {
        $cols = $withHiddenColumns ? $schedule->getColumns() : $schedule->getVisibleColumns();

        // never expose the special options column
        $cols = $cols->filter(function($col) {
            return $col->getName() !== Schedule::OPTION_COLUMN_NAME;
        });

        return $cols;
    }
}
