<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use function is_string;

final class GravatarHashValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var GravatarHash $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, 'string');

            // separate multiple types using pipes
            // throw new UnexpectedValueException($value, 'string|int');
        }

        $gravatar = strtolower(trim($value));

        // it's already a hash
        if (preg_match('/^[0-9a-f]{32}$/', $value)) {
            return;
        }

        if (mb_strlen($gravatar) === 0) {
            return;
        }

        // Hacky, but works :)
        $this->context->getObject()->setGravatar(md5($gravatar));

        // TODO: implement the validation here
        /*$this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation()
        ;*/
    }
}
