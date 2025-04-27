<?php

namespace App\Horaro\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;

class UpdatePasswordDto
{
    #[SecurityAssert\UserPassword]
    private string $current;

    #[Assert\NotBlank]
    #[Assert\NotCompromisedPassword]
    #[Assert\PasswordStrength]
    private string $password;

    #[Assert\EqualTo(propertyPath: 'password')]
    private string $password2;
}
