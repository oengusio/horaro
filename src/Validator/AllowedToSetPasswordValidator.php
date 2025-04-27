<?php

namespace App\Validator;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use function is_string;
use function is_null;

final class AllowedToSetPasswordValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage
    )
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var AllowedToSetPassword $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, 'string');
        }

        $user = $this->tokenStorage->getToken()->getUser();

        if (!($user instanceof User)) {
            throw new ConstraintDefinitionException(\sprintf('The "%s" class must implement the "%s" interface.', get_debug_type($user), PasswordAuthenticatedUserInterface::class));
        }

        if (is_null($user->getPassword()) || $user->getPassword() === '') {
            $this->context->buildViolation($constraint->message)
                          ->setParameter('{{ value }}', $value)
                          ->addViolation();
        }
    }
}
