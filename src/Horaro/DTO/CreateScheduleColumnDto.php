<?php

namespace App\Horaro\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateScheduleColumnDto
{
    #[Assert\NotBlank]
    private string $name;

    #[Assert\NotNull]
    private bool $hidden;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }
}
