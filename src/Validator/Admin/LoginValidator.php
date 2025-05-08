<?php

namespace App\Validator\Admin;

use App\Horaro\Traits\CanGetUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class LoginValidator extends ConstraintValidator
{
    use CanGetUser;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var Login $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        $user = $this->getUserFromAdminRequest();

        if (!$user) {
            $this->context->buildViolation('No such user found')
                          ->addViolation();

            return;
        }

        if ($user->isOAuthAccount()) {
            $this->context->buildViolation($constraint->message)
                          ->addViolation();
        }
    }
}
