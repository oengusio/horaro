<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Schedule;
use App\Entity\ScheduleColumn;
use App\Horaro\DTO\CreateScheduleDto;
use App\Horaro\DTO\EventDescriptionUpdateDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ScheduleController extends BaseController
{
    #[IsGranted('edit', 'event')]
    #[Route('/-/events/{event_e}/schedules/new', name: 'app_backend_schedule_new', methods: ['GET'])]
    public function newScheduleForm(#[ValueResolver('event_e')] Event $event): Response
    {
        if ($this->exceedsMaxSchedules($event)) {
            return $this->redirect(
                '/-/events/'.$this->encodeID($event->getId(), 'event')
            );
        }

        return $this->renderForm($event);
    }

    #[IsGranted('edit', 'event')]
    #[Route('/-/events/{event_e}/schedules', name: 'app_backend_schedule_create', methods: ['POST'])]
    public function create(
        #[ValueResolver('event_e')] Event      $event,
        #[MapRequestPayload] CreateScheduleDto $createDto,
    ): Response
    {
        if ($this->exceedsMaxSchedules($event)) {
            return $this->redirect(
                '/-/events/'.$this->encodeID($event->getId(), 'event')
            );
        }

        $schedule = new Schedule();
        $dtoStartDate = $createDto->getStartDate();
        $dtoStartTime = $createDto->getStartTime();
        $startDateTime = \DateTime::createFromFormat('Y-m-d G:i', "$dtoStartDate $dtoStartTime");

        $schedule
            ->setEvent($event)
            ->setName($createDto->getName())
            ->setSlug($createDto->getSlug())
            ->setTimezone($createDto->getTimezone())
            ->setStart($startDateTime)
            ->setWebsite($createDto->getWebsite())
            ->setTwitter($createDto->getTwitter())
            ->setTwitch($createDto->getTwitch())
            ->setTheme($createDto->getTheme())
            ->setSecret($createDto->getSecret())
            ->setHiddenSecret($createDto->getHiddenSecret())
            ->setSetupTime($createDto->getParsedSetupTime())
            ->setMaxItems($this->config->getByKey('max_schedule_items', 200)->getValue())
            ->touch();

        $column = new ScheduleColumn();
        $column
            ->setSchedule($schedule)
            ->setPosition(1)
            ->setName('Description');

        $this->entityManager->persist($schedule);
        $this->entityManager->persist($column);
        $this->entityManager->flush();

        // done

        $this->addSuccessMsg('Your new schedule has been created.');

        return $this->redirect('/-/schedules/'.$this->encodeID($schedule->getId(), 'schedule'));
    }

    #[IsGranted('edit', 'schedule')]
    #[Route('/-/schedules/{schedule_e}', name: 'app_backend_schedule_detail', methods: ['GET'])]
    public function index(#[ValueResolver('schedule_e')] Schedule $schedule): Response
    {
        $items = [];
        $columnIDs = [];

        foreach ($schedule->getItems() as $item) {
            $extra = [];

            foreach ($item->getExtra() as $colID => $value) {
                $extra[$this->encodeID($colID, 'schedule.column')] = $value;
            }

            $items[] = [
                $this->encodeID($item->getId(), 'schedule.item'),
                $item->getLengthInSeconds(),
                $extra,
            ];
        }

        foreach ($schedule->getColumns() as $column) {
            $columnIDs[] = $this->encodeID($column->getId(), 'schedule.column');
        }

        return $this->render('schedule/detail.twig', [
            'schedule' => $schedule,
            'items' => $items ?: null,
            'columns' => $columnIDs,
            'maxItems' => $this->config->getByKey('max_schedule_items', 200)->getValue(),
        ]);
    }

    #[IsGranted('edit', 'schedule')]
    #[Route('/-/schedules/{schedule_e}/edit', name: 'app_backend_schedule_edit', methods: ['GET'])]
    public function editSchedule(#[ValueResolver('schedule_e')] Schedule $schedule): Response
    {
        return $this->renderForm($schedule->getEvent(), $schedule);
    }

    #[IsGranted('edit', 'schedule')]
    #[Route('/-/schedules/{schedule_e}', name: 'app_backend_schedule_edit_save', methods: ['PUT'])]
    public function save(
        #[ValueResolver('schedule_e')] Schedule $schedule,
        #[MapRequestPayload] CreateScheduleDto  $createDto,
    ): Response
    {
        $dtoStartDate = $createDto->getStartDate();
        $dtoStartTime = $createDto->getStartTime();
        $startDateTime = \DateTime::createFromFormat('Y-m-d G:i', "$dtoStartDate $dtoStartTime");

        $schedule
            ->setName($createDto->getName())
            ->setSlug($createDto->getSlug())
            ->setTimezone($createDto->getTimezone())
            ->setStart($startDateTime)
            ->setWebsite($createDto->getWebsite())
            ->setTwitter($createDto->getTwitter())
            ->setTwitch($createDto->getTwitch())
            ->setTheme($createDto->getTheme())
            ->setSecret($createDto->getSecret())
            ->setHiddenSecret($createDto->getHiddenSecret())
            ->setSetupTime($createDto->getParsedSetupTime())
            ->touch();

        $this->entityManager->flush();

        // done

        $this->addSuccessMsg('Your schedule has been updated.');

        return $this->redirect('/-/schedules/'.$this->encodeID($schedule->getId(), 'schedule'));
    }

    #[IsGranted('edit', 'schedule')]
    #[Route('/-/schedules/{schedule_e}/description', name: 'app_backend_schedule_edit_save_description', methods: ['PUT'])]
    public function saveDescription(
        #[ValueResolver('schedule_e')] Schedule        $schedule,
        #[MapRequestPayload] EventDescriptionUpdateDto $dto,
    ): Response
    {
        $schedule
            ->setDescription($dto->getDescription())
            ->touch();

        $this->entityManager->flush();

        // done

        $this->addSuccessMsg('Your schedule description has been updated.');

        return $this->redirect('/-/schedules/'.$this->encodeID($schedule->getId(), 'schedule'));
    }

    #[IsGranted('edit', 'schedule')]
    #[Route('/-/schedules/{schedule_e}/delete', name: 'app_backend_schedule_delete_confirmation', methods: ['GET'])]
    public function confirmDelete(#[ValueResolver('schedule_e')] Schedule $schedule): Response
    {
        return $this->render('schedule/confirmation.twig', ['schedule' => $schedule]);
    }

    #[IsGranted('edit', 'schedule')]
    #[Route('/-/schedules/{schedule_e}', name: 'app_backend_schedule_delete', methods: ['DELETE'])]
    public function delete(#[ValueResolver('schedule_e')] Schedule $schedule): Response
    {
        $eventId = $schedule->getEvent()->getId();

        $this->entityManager->remove($schedule);
        $this->entityManager->flush();

        $this->addSuccessMsg('The requested schedule has been deleted.');

        return $this->redirect('/-/events/'.$this->encodeID($eventId, 'event'));
    }

    protected function renderForm(Event $event, Schedule $schedule = null, $result = null): Response
    {
        $timezones = \DateTimeZone::listIdentifiers();

        return $this->render('schedule/form.twig', [
            'event' => $event,
            'timezones' => $timezones,
            'schedule' => $schedule,
            'result' => $result,
            'themes' => $this->getParameter('horaro.themes'),
            'defaultTheme' => $event->getTheme(),
        ]);
    }
}
