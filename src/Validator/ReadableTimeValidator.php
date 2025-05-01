<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class ReadableTimeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var ReadableTime $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        $parser = new \App\Horaro\Library\ReadableTime();
        $time   = trim($value);

        try {
            $parsed = $parser->parse($time);


            $this->context->getObject()->setParsedSetupTime($parsed);
        }
        catch (\InvalidArgumentException $e) {
            $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
        }
    }
}
