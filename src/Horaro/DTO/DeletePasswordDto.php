<?php

namespace App\Horaro\DTO;

use App\Validator as HoraroAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;

class DeletePasswordDto
{
    #[HoraroAssert\AllowedToSetPassword]
    #[SecurityAssert\UserPassword]
    private string $current;

    public function getCurrent(): string
    {
        return $this->current;
    }

    public function setCurrent(string $current): void
    {
        $this->current = $current;
    }
}
