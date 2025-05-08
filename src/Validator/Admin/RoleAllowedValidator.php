<?php

namespace App\Validator\Admin;

use App\Horaro\RoleManager;
use App\Horaro\Traits\CanGetUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class RoleAllowedValidator extends ConstraintValidator
{
    use CanGetUser;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RoleManager $roleManager,
    )
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var RoleAllowed $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        $user = $this->getUserFromAdminRequest();

        if (!$user) {
            $this->context->buildViolation('No such user found')
                          ->addViolation();

            return;
        }

        $editor = $this->getUserFromToken($this->tokenStorage->getToken());

        $rm = $this->roleManager;

        if ($rm->userIsSuperior($user, $editor) || $rm->userIsColleague($user, $editor)) {
            $this->context->buildViolation('cannot change superior\'s or colleague\'s roles')
                          ->addViolation();

            return;
        }

        try {
            $roleWeight   = $rm->getWeight($value);
            $editorWeight = $rm->getWeight($editor->getRole());

            // cannot give a role that's higher or the same as the editor's one
            if ($roleWeight >= $editorWeight) {
                $this->context->buildViolation('You may not assign this role.')
                              ->addViolation();
            }
        }
        catch (\Exception $e) {
            $this->context->buildViolation('Unknown role given')
                          ->addViolation();
        }
    }
}
