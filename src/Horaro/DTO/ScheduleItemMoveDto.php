<?php

namespace App\Horaro\DTO;

use App\Validator as HoraroAssert;
use Symfony\Component\Validator\Constraints as Assert;

class ScheduleItemMoveDto
{
    // Is validated for existence on schedule in the controller
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private string|int $item; // Will be an int when debug mode is on KEK

    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(1)]
    private int $position;

    public function getItem(): int|string
    {
        return $this->item;
    }

    public function setItem(int|string $item): void
    {
        $this->item = $item;
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
