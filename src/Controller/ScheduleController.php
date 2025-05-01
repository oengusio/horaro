<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Schedule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ScheduleController extends BaseController
{
    #[IsGranted('edit', 'event')]
    #[Route('/-/events/{event_e}/schedules/new', name: 'app_backend_schedule_new', methods: ['GET'])]
    public function newScheduleForm(#[ValueResolver('event_e')] Event $event): Response {
        if ($this->exceedsMaxSchedules($event)) {
            return $this->redirect(
                '/-/events/'.$this->obscurityCodec->encode($event->getId(), 'event')
            );
        }
        return $this->renderForm($event);
    }

    #[IsGranted('edit', 'schedule')]
    #[Route('/-/schedules/{schedule_e}', name: 'app_backend_schedule_detail', methods: ['GET'])]
    public function index(#[ValueResolver('schedule_e')] Schedule $schedule): Response
    {
        $items     = [];
        $columnIDs = [];

        foreach ($schedule->getItems() as $item) {
            $extra = [];

            foreach ($item->getExtra() as $colID => $value) {
                $extra[$this->encodeID($colID, 'schedule.column')] = $value;
            }

            $items[] = [
                $this->encodeID($item->getId(), 'schedule.item'),
                $item->getLengthInSeconds(),
                $extra
            ];
        }

        foreach ($schedule->getColumns() as $column) {
            $columnIDs[] = $this->encodeID($column->getId(), 'schedule.column');
        }

        return $this->render('schedule/detail.twig', [
            'schedule' => $schedule,
            'items'    => $items ?: null,
            'columns'  => $columnIDs,
            'maxItems' => $this->config->getByKey('max_schedule_items', 200)->getValue(),
        ]);
    }


    protected function renderForm(Event $event, Schedule $schedule = null, $result = null): Response {
        $timezones = \DateTimeZone::listIdentifiers();

        return $this->render('schedule/form.twig', [
            'event'        => $event,
            'timezones'    => $timezones,
            'schedule'     => $schedule,
            'result'       => $result,
            'themes'       => $this->getParameter('horaro.themes'),
            'defaultTheme' => $event->getTheme()
        ]);
    }
}
