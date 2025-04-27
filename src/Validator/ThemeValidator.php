<?php

namespace App\Validator;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use function key_exists;

final class ThemeValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ContainerBagInterface $params,
    )
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var Theme $constraint */

        $themes = $this->params->get('horaro.themes');

        if (!key_exists($value, $themes)) {
            $this->context->buildViolation($constraint->message)
                          ->setParameter('{{ value }}', $value)
                          ->addViolation();
        }
    }
}
