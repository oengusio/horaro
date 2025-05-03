<?php

namespace App\Horaro\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateScheduleItemDto
{
    #[Assert\NotNull]
    #[Assert\GreaterThan(
        value: 1,
        message: 'Schedule items must at least last for one second.',
    )]
    #[Assert\LessThan(
        value: 7*24*3600,
        message: 'Schedule items cannot last for more than 7 days.',
    )]
    private int $length;

    // TODO: validate columns
    #[Assert\NotNull]
    private array $columns;

    public function getLength(): int
    {
        return $this->length;
    }

    public function setLength(int $length): void
    {
        $this->length = $length;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

}
