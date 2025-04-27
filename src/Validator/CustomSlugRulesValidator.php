<?php

namespace App\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class CustomSlugRulesValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    // TODO: make this work for schedules and updated events
    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var CustomSlugRules $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        if (preg_match('/^-|-$/', $value)) {
            $this->context->buildViolation('The slug cannot start or end with a dash.')
                         ->addViolation();

            return;
        }

        if (in_array($value, ['-', 'assets'], true)) {
            $this->context->buildViolation('The slug "{{ value }}" is reserved for internal usage.')
                          ->setParameter('{{ value }}', $value)
                         ->addViolation();

            return;
        }

        $existing = $this->entityManager
            ->getRepository($constraint->entity)
            ->findOneBy(['slug' => $value]);

        if ($existing) {
            $this->context->buildViolation('The slug "{{ value }}" is already in use, sorry.')
                          ->setParameter('{{ value }}', $value)
                          ->addViolation();
        }
    }
}
