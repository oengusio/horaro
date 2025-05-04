<?php

namespace App\Horaro\Pager;

use League\Fractal\Resource\Collection;

interface PagerInterface
{
    public function getOffset(): int;
    public function getPageSize(): int;
    public function getOrder(array $allowed, $default): string;
    public function getDirection($default): string;
    public function setCurrentCollection(Collection $collection): void;
    public function createData(): array;
}
