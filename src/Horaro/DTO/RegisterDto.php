<?php

namespace App\Horaro\DTO;

use App\Validator as HoraroAssert;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterDto
{
    #[Assert\NotBlank]
    #[HoraroAssert\NonTakenUsername]
    private string $login;

    #[Assert\NotBlank]
    #[Assert\NotCompromisedPassword]
    #[Assert\PasswordStrength]
    #[Assert\NotEqualTo(
        value: 'secret123',
        message: 'You just had to try it out, didn\'t you? Please choose something else.',
    )]
    private string $password;

    #[Assert\EqualTo(propertyPath: 'password')]
    private string $password2;

    #[Assert\NotBlank]
    private string $display_name;

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
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

    public function getDisplayName(): string
    {
        return $this->display_name;
    }

    public function setDisplayName(string $display_name): void
    {
        $this->display_name = $display_name;
    }
}
