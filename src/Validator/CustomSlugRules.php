<?php

namespace App\Validator;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class CustomSlugRules extends Constraint
{
    public string $message = 'Not a valid slug';

    // You can use #[HasNamedArguments] to make some constraint options required.
    // All configurable options must be passed to the constructor.
    #[HasNamedArguments]
    public function __construct(
        public string $entity,
        public string $mode = 'strict',
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);
    }
}
