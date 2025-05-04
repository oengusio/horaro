<?php

namespace App\Controller;

use App\Entity\Schedule;
use App\Entity\ScheduleColumn;
use App\Horaro\DTO\CreateScheduleColumnDto;
use App\Horaro\DTO\ScheduleColumnMoveDto;
use App\Horaro\Ex\ScheduleColumnNotFoundException;
use App\Horaro\Library\ObscurityCodec;
use App\Horaro\Service\ObscurityCodecService;
use App\Repository\ConfigRepository;
use App\Repository\ScheduleColumnRepository;
use App\Repository\ScheduleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ScheduleColumnController extends BaseController
{
    public function __construct(
        private readonly ScheduleRepository       $scheduleRepository,
        private readonly ScheduleColumnRepository $columnRepository,
        ConfigRepository                          $config,
        Security                                  $security,
        EntityManagerInterface                    $entityManager,
        ObscurityCodecService                     $obscurityCodec,
    )
    {
        parent::__construct($config, $security, $entityManager, $obscurityCodec);
    }

    #[IsGranted('edit', 'schedule')]
    #[Route('/-/schedules/{schedule_e}/columns/edit', name: 'app_schedule_column_edit', methods: ['GET'])]
    public function edit(#[ValueResolver('schedule_e')] Schedule $schedule): Response
    {
        $extra = $schedule->getExtra();
        $columns = [
            [Schedule::COLUMN_SCHEDULED, $extra['texts'][Schedule::COLUMN_SCHEDULED] ?? 'Scheduled', -1, false, true],
            [Schedule::COLUMN_ESTIMATE, $extra['texts'][Schedule::COLUMN_ESTIMATE] ?? 'Estimated', 0, false, true],
        ];

        foreach ($schedule->getColumns() as $col) {
            $columns[] = [
                $this->encodeID($col->getID(), ObscurityCodec::SCHEDULE_COLUMN),
                $col->getName(),
                $col->getPosition(),
                $col->isHidden(),
                false,
            ];
        }

        return $this->render('schedule/columns.twig', ['schedule' => $schedule, 'columns' => $columns]);
    }

    #[IsGranted('edit', 'schedule')]
    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/schedules/{schedule_e}/columns', name: 'app_schedule_column_new', methods: ['POST'])]
    public function createNew(
        #[ValueResolver('schedule_e')] Schedule      $schedule,
        #[MapRequestPayload] CreateScheduleColumnDto $dto,
    ): Response
    {
        if (!$dto->isHidden() && $this->exceedsMaxScheduleColumns($schedule)) {
            throw new BadRequestHttpException('You cannot create more columns for this schedule.');
        }

        $schedRepo = $this->scheduleRepository;
        $colRepo = $this->columnRepository;

        $col = $this->entityManager->wrapInTransaction(static function (EntityManagerInterface $em) use ($schedule, $dto, $schedRepo, $colRepo) {
            $schedRepo->transientLock($schedule);

            // find max position
            $last = $colRepo->findOneBy(
                ['schedule' => $schedule],
                ['position' => 'DESC'],
            );
            $max = $last ? $last->getPosition() : 0;

            // prepare new column

            $col = new ScheduleColumn();
            $col->setSchedule($schedule);
            $col->setPosition($max + 1);
            $col->setName($dto->getName());
            $col->setHidden($dto->isHidden());

            $schedule->touch();

            // store it

            $em->persist($col);

            return $col;
        });

        // respond

        return $this->json([
            'data' => [
                'id' => $this->encodeID($col->getId(), ObscurityCodec::SCHEDULE_COLUMN),
                'pos' => $col->getPosition(),
                'name' => $col->getName(),
                'hidden' => $col->isHidden(),
            ],
        ], 201);
    }

    #[IsGranted('edit', 'schedule')]
    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/schedules/{schedule_e}/columns/fixed/{column_key}', name: 'app_schedule_column_update_fixed', methods: ['PUT'])]
    public function updateFixed(
        #[ValueResolver('schedule_e')] Schedule              $schedule,
        #[MapRequestPayload] CreateScheduleColumnDto         $dto, // we love reusing dtos when possible :D
        string $column_key
    ): Response
    {
        if (!in_array($column_key, [Schedule::COLUMN_SCHEDULED, Schedule::COLUMN_ESTIMATE], true)) {
            throw new ScheduleColumnNotFoundException();
        }

        // update column

        $extra = $schedule->getExtra();
        $extra['texts'][$column_key] = $dto->getName();

        $schedule->setExtra($extra);
        $schedule->touch();

        // store it

        $this->entityManager->flush();

        // respond

        return $this->json([
            'data' => [
                'id'   => $column_key,
                'pos'  => $column_key === Schedule::COLUMN_SCHEDULED ? -1 : 0,
                'name' => $dto->getName(),
            ]
        ]);
    }

    #[IsGranted('edit', 'schedule')]
    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/schedules/{schedule_e}/columns/move', name: 'app_schedule_column_move', methods: ['POST'])]
    public function moveItems(
        #[ValueResolver('schedule_e')] Schedule              $schedule,
        #[MapRequestPayload] ScheduleColumnMoveDto $dto,
    ): Response {
        $schedRepo = $this->scheduleRepository;
        $colRepo = $this->columnRepository;

        $columnId = $this->decodeID($dto->getColumn(), ObscurityCodec::SCHEDULE_COLUMN);

        /** @var ScheduleColumn $column */
        $column = $this->entityManager->wrapInTransaction(static function (EntityManagerInterface $em) use ($schedule, $dto, $schedRepo, $colRepo, $columnId) {
            $schedRepo->transientLock($schedule);

            $foundColumn = $colRepo->findOneBy([
                'id' => $columnId,
                'schedule' => $schedule,
            ]);

            if (!$foundColumn) {
                throw new NotFoundHttpException('No such schedule item in this schedule');
            }

            $curPos = $foundColumn->getPosition();

            if ($curPos < 1) {
                throw new BadRequestHttpException('This item is already at position 0. This should never happen.');
            }

            $target = $dto->getPosition();

            if ($target === $curPos) {
                throw new ConflictHttpException('This would be a NOP.');
            }

            $last = $colRepo->findOneBy(
                ['schedule' => $schedule],
                ['position' => 'DESC']
            );
            $max = $last ? $last->getPosition() : 0;

            if ($target > $max) {
                throw new BadRequestHttpException('Target position ('.$target.') is greater than the last position ('.$max.').');
            }

            // prepare chunk move
            $up = $target < $curPos;
            $relation = $up ? '+' : '-';
            [$a, $b] = $up ? [$target, $curPos] : [$curPos, $target];

            // move items between old and new position
            $colRepo->movePosition($schedule, $a, $b, $relation);

            $schedule->touch();
            $foundColumn->setPosition($target);

            return $foundColumn;
        });

        return $this->json([
            'data' => [
                'id' => $this->encodeID($column->getId(), ObscurityCodec::SCHEDULE_COLUMN),
                'pos' => $column->getPosition(),
            ],
        ]);
    }

    #[IsGranted('edit', 'schedule')]
    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/schedules/{schedule_e}/columns/{schedule_column_e}', name: 'app_schedule_column_update', methods: ['PUT'])]
    public function updateNormal(
        #[ValueResolver('schedule_e')] Schedule              $schedule,
        #[ValueResolver('schedule_column_e')] ScheduleColumn $column,
        #[MapRequestPayload] CreateScheduleColumnDto         $dto, // we love reusing dtos when possible :D
    ): Response
    {
        // update column
        $column->setName($dto->getName());
        $column->setHidden($dto->isHidden());
        $schedule->touch();

        // store it
        $this->entityManager->flush();

        // respond
        return $this->json([
            'data' => [
                'id' => $this->encodeID($column->getId(), ObscurityCodec::SCHEDULE_COLUMN),
                'pos' => $column->getPosition(),
                'name' => $column->getName(),
                'hidden' => $column->isHidden(),
            ],
        ]);
    }

    #[IsGranted('edit', 'schedule')]
    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/schedules/{schedule_e}/columns/{schedule_column_e}', name: 'app_schedule_column_delete', methods: ['DELETE'])]
    public function delete(
        #[ValueResolver('schedule_e')] Schedule              $schedule,
        #[ValueResolver('schedule_column_e')] ScheduleColumn $column,
    ): Response
    {
        // do not allow to delete the only column

        if ($schedule->getColumns()->count() === 1) {
            throw new ConflictHttpException('The last column cannot be deleted.');
        }


        $schedRepo = $this->scheduleRepository;
        $colRepo = $this->columnRepository;

        $this->entityManager->wrapInTransaction(static function (EntityManagerInterface $em) use ($schedule, $column, $schedRepo, $colRepo) {
            $schedRepo->transientLock($schedule);

            $columnId = $column->getId();
            // re-fetch the column to get its actual current position
            $freshCol = $colRepo->find($columnId);

            $colRepo->movePreDelOnePositionUp($schedule, $freshCol->getPosition());

            // Cleanup schedule item data, no reason to store stuff we don't need anymore
            foreach ($schedule->getItems() as $item) {
                $itemExtra = $item->getExtra();

                // Not sure what unset does for unknown keys, so better safe than sorry
                if (isset($itemExtra[$columnId])) {
                    unset($itemExtra[$columnId]);
                }

                $item->setExtra($itemExtra);
            }

            $schedule->touch();

            $em->remove($column);
        });

        return $this->json(['data' => true]);
    }
}
