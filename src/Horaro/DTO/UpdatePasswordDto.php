<?php

namespace App\Horaro\DTO;

use App\Validator as HoraroAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;

class UpdatePasswordDto
{
    #[HoraroAssert\AllowedToSetPassword]
    #[SecurityAssert\UserPassword]
    private string $current;

    #[Assert\NotBlank]
    #[Assert\NotCompromisedPassword]
    #[Assert\PasswordStrength]
    private string $password;

    #[Assert\EqualTo(propertyPath: 'password')]
    private string $password2;

    public function getCurrent(): string
    {
        return $this->current;
    }

    public function setCurrent(string $current): void
    {
        $this->current = $current;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getPassword2(): string
    {
        return $this->password2;
    }

    public function setPassword2(string $password2): void
    {
        $this->password2 = $password2;
    }
}
