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
use function ucfirst;

final class CustomSlugRulesValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack           $requestStack,
        private readonly ObscurityCodecService  $obscurityCodec,
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
        $existing = $this->lookupExistingInDb($constraint, $value);

        if ($existing) {
            $ref = $this->decodeConstraintItemId($constraint);

            if (!$ref || $existing->getId() !== $ref) {
                $this->context->buildViolation('The slug "{{ value }}" is already in use, sorry.')
                    ->setParameter('{{ value }}', $value)
                    ->addViolation();
            }
        }
    }

    private function lookupExistingInDb(CustomSlugRules $constraint, string $slug): ?object
    {
        $searchParams = [
            'slug' => $slug,
        ];

        if ($constraint->parent) {
            $parentName = $this->parseParameterName($constraint->parent);
            $parentId = $this->decodeItemIdFromParameter(
                $parentName,
                $constraint->paramSuffix,
                $constraint->idNeedsDecoding,
            );

            if (!$parentId) {
                $entityId = $this->decodeConstraintItemId($constraint);

                if ($entityId) {
                    $entity = $this->entityManager->getRepository($constraint->entity)->find($entityId); // +1 db call

                    if ($entity) {
                        $getter = 'get'.ucfirst($parentName); // eg "getEvent"

                        if (method_exists($entity, $getter) && $parent = $entity->$getter()) { // +1 db call
                            $parentId = $parent->getId();
                        }
                    }
                }
            }

            if ($parentId) {
                $searchParams[$parentName] = $parentId;
            }
        }

        // This db call is the only one that should be required
        // Could possibly restructure url to always include parent id (would be more restfull even though it's a UI)
        // That would save the 2 database calls I had to introduce just now
        // https://github.com/oengusio/horaro/issues/54
        return $this->entityManager
            ->getRepository($constraint->entity)
            ->findOneBy($searchParams);
    }

    private function parseParameterName(string $paramName): string
    {
        $boom = explode('\\', strtolower($paramName));

        return $boom[count($boom) - 1];
    }

    private function decodeConstraintItemId(CustomSlugRules $constraint): ?int
    {
        return $this->decodeItemIdFromParameter(
            $this->parseParameterName($constraint->entity),
            $constraint->paramSuffix, $constraint->idNeedsDecoding,
        );
    }

    private function decodeItemIdFromParameter(string $paramName, string $suffix, bool $needsDecoding): ?int
    {
        $requestArg = $paramName.$suffix;
        $paramId = $this->requestStack->getCurrentRequest()->attributes->get($requestArg);

        if (!$paramId) {
            return null;
        }

        if ($needsDecoding) {
            return $this->obscurityCodec->decode($paramId, $paramName);
        }

        return $paramId;

        // No need to fetch the entity if we just need the id, we already have that
        /*return $this->entityManager
            ->getRepository($constraint->entity)
            ->findOneBy(['id' => $decodedId]);*/
    }

    private function fetchParentFromDb(\App\Entity\Event|\App\Entity\Schedule $existing, string $paramName): ?object
    {
        $casedName = ucfirst($paramName);
        $getterName = "get{$casedName}";

        if (method_exists($existing, $getterName)) {
            return $existing->$getterName();
        }


        return null;
    }
}
