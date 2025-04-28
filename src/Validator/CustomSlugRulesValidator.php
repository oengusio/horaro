<?php

namespace App\Validator;

use App\Horaro\Service\ObscurityCodecService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use function preg_match;
use function in_array;
use function strtolower;
use function explode;
use function count;

final class CustomSlugRulesValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly ObscurityCodecService $obscurityCodec,
    )
    {
    }

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

        /** @var \App\Entity\Event|\App\Entity\Schedule $existing */
        $existing = $this->entityManager
            ->getRepository($constraint->entity)
            ->findOneBy(['slug' => $value]);

        if ($existing) {
            $ref = $this->decodeItemId($constraint);

            if ($ref && $existing->getId() !== $ref) {
                $this->context->buildViolation('The slug "{{ value }}" is already in use, sorry.')
                              ->setParameter('{{ value }}', $value)
                              ->addViolation();
            }
        }
    }

    private function getParameterName(CustomSlugRules $constraint): string {
        $entityName = strtolower($constraint->entity);
        $boom = explode('\\', $entityName);

        return $boom[count($boom) - 1];
    }

    private function decodeItemId(CustomSlugRules $constraint): ?int {
        $paramName = $this->getParameterName($constraint);
        $requestArg = "{$paramName}_e";
        $paramId = $this->requestStack->getCurrentRequest()->attributes->get($requestArg);

        if (!$paramId) {
            return null;
        }

        return $this->obscurityCodec->decode($paramId, $paramName);

        // No need to fetch the entity if we just need the id, we already have that
        /*return $this->entityManager
            ->getRepository($constraint->entity)
            ->findOneBy(['id' => $decodedId]);*/
    }
}
