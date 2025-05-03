<?php

namespace App\Controller;

use App\Entity\Schedule;
use App\Horaro\Library\ObscurityCodec;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ScheduleColumnController extends BaseController
{
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
}
