<?php

namespace App\Validator;

use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class NonTakenUsernameValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepository $userRepository,
    )
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var NonTakenUsername $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        if (str_starts_with($value, 'oauth:')) {
            $this->context
                ->buildViolation('Please sign up with twitch if you want an oauth account :)')
                ->addViolation();

            return;
        }

        $foundUser = $this->userRepository->findOneBy([
            'login' => $value,
        ]);

        if ($foundUser) {
            $this->context->buildViolation($constraint->message)
                          ->setParameter('{{ value }}', $value)
                          ->addViolation();
        }
    }
}
