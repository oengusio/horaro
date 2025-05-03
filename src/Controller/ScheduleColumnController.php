<?php

namespace App\Controller;

use App\Entity\Schedule;
use App\Entity\ScheduleColumn;
use App\Horaro\DTO\CreateScheduleColumnDto;
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

    // TODO: move, update fixed & delete

    public function updateFixed()
    {
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
}
