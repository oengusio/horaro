<?php

namespace App\Validator;

use App\Entity\Schedule;
use App\Horaro\Library\ObscurityCodec;
use App\Horaro\Service\ObscurityCodecService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use function is_array;
use function is_string;
use function trim;
use function mb_substr;

final class ScheduleItemColumnValidator extends ConstraintValidator
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
        /* @var ScheduleItemColumn $constraint */

        if (null === $value) {
            return;
        }

        if (!is_array($value)) {
            $this->context->buildViolation('Can only be used on an array')
                          ->addViolation();

            return;
        }

        // Empty columns are a valid value
        if (empty($value)) {
            return;
        }

        try {
            $schedule = $this->getSchedule();
            $columns = $schedule->getColumns();
            $result = [];

            foreach ($columns as $column) {
                $colID = $column->getId();
                $encodedID = $this->obscurityCodec->encode($colID, ObscurityCodec::SCHEDULE_COLUMN);

                if (isset($value[$encodedID]) && is_string($value[$encodedID])) {
                    $val = trim(mb_substr(trim($value[$encodedID]), 0, 512));

                    $result[$colID] = $val;
                }
            }

            $this->context->getObject()->{$constraint->setterFn}($result);
        } catch (\Throwable $e) {
            $this->context->buildViolation('Failed to validate schedule item columns: {{ msg }}')
                          ->setParameter('{{ msg }}', $e->getMessage())
                          ->addViolation();
        }
    }

    private function getSchedule(): ?Schedule
    {
        $paramId = $this->requestStack->getCurrentRequest()->attributes->get('schedule_e');

        if (!$paramId) {
            return null;
        }

        $decodedId = $this->obscurityCodec->decode($paramId, ObscurityCodec::SCHEDULE);

        return $this->entityManager
            ->getRepository(Schedule::class)
            ->findOneBy(['id' => $decodedId]);
    }
}
