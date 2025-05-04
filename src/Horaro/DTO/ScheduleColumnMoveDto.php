<?php

namespace App\Horaro\DTO;

use App\Validator as HoraroAssert;
use Symfony\Component\Validator\Constraints as Assert;

class ScheduleColumnMoveDto
{
    // Is validated for existence on schedule in the controller
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private string|int $column; // Will be an int when debug mode is on KEK

    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(1)]
    private int $position;

    public function getColumn(): int|string
    {
        return $this->column;
    }

    public function setColumn(int|string $column): void
    {
        $this->column = $column;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
}
