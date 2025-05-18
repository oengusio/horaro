<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Schedule;
use App\Entity\ScheduleColumn;
use App\Form\Type\EventDescriptionType;
use App\Form\Type\ScheduleType;
use App\Horaro\DTO\CreateScheduleDto;
use App\Horaro\DTO\EventDescriptionUpdateDto;
use App\Horaro\Library\ObscurityCodec;
use App\Horaro\Service\ScheduleTransformerService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ScheduleController extends BaseController
{
    #[IsGranted('edit', 'event')]
    #[Route('/-/events/{event_e}/schedules/new', name: 'app_backend_schedule_new', methods: ['GET', 'POST'])]
    public function newScheduleForm(Request $request, #[ValueResolver('event_e')] Event $event): Response
    {
        if ($this->exceedsMaxSchedules($event)) {
            return $this->redirectToRoute('app_backend_event_detail', [
                'event_e' => $this->encodeID($event->getId(), ObscurityCodec::EVENT),
            ]);
        }

        $form = $this->createForm(ScheduleType::class, new CreateScheduleDto());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CreateScheduleDto $createDto */
            $createDto = $form->getData();

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

            return $this->redirectToRoute('app_backend_schedule_detail', [
                'schedule_e' => $this->encodeID($schedule->getId(), ObscurityCodec::SCHEDULE),
            ]);
        }

        return $this->renderForm($event, $form);
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
                $extra[$this->encodeID($colID, ObscurityCodec::SCHEDULE_COLUMN)] = $value;
            }

            $items[] = [
                $this->encodeID($item->getId(), ObscurityCodec::SCHEDULE_ITEM),
                $item->getLengthInSeconds(),
                $extra,
            ];
        }

        foreach ($schedule->getColumns() as $column) {
            $columnIDs[] = $this->encodeID($column->getId(), ObscurityCodec::SCHEDULE_COLUMN);
        }

        return $this->render('schedule/detail.twig', [
            'schedule' => $schedule,
            'items' => $items ?: null,
            'columns' => $columnIDs,
            'maxItems' => $this->config->getByKey('max_schedule_items', 200)->getValue(),
        ]);
    }

    #[IsGranted('edit', 'schedule')]
    #[Route('/-/schedules/{schedule_e}/edit', name: 'app_backend_schedule_edit', methods: ['GET', 'PUT'])]
    public function editSchedule(Request $request, #[ValueResolver('schedule_e')] Schedule $schedule): Response
    {
        $form = $this->createForm(ScheduleType::class, CreateScheduleDto::fromSchedule($schedule), [
            'method' => 'PUT',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CreateScheduleDto $createDto */
            $createDto = $form->getData();

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

            return $this->redirectToRoute('app_backend_schedule_detail', [
                'schedule_e' => $this->encodeID($schedule->getId(), ObscurityCodec::SCHEDULE),
            ]);
        }

        return $this->renderForm($schedule->getEvent(), $form, $schedule);
    }

    #[IsGranted('edit', 'schedule')]
    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/schedules/{schedule_e}/description', name: 'app_backend_schedule_edit_save_description', methods: ['PUT'])]
    public function saveDescription(
        Request                                 $request,
        #[ValueResolver('schedule_e')] Schedule $schedule,
    ): Response
    {
        $descForm = $this->createForm(EventDescriptionType::class, EventDescriptionUpdateDto::fromEvent($schedule), [
            'method' => 'PUT',
        ]);

        $descForm->handleRequest($request);

        if ($descForm->isSubmitted() && $descForm->isValid()) {
            /** @var EventDescriptionUpdateDto $dto */
            $dto = $descForm->getData();

            $schedule
                ->setDescription($dto->getDescription())
                ->touch();

            $this->entityManager->flush();

            // done

            $this->addSuccessMsg('Your schedule description has been updated.');

            return $this->redirectToRoute('app_backend_schedule_detail', [
                'schedule_e' => $this->encodeID($schedule->getId(), ObscurityCodec::SCHEDULE),
            ]);
        }

        $form = $this->createForm(ScheduleType::class, CreateScheduleDto::fromSchedule($schedule));

        return $this->renderForm($schedule->getEvent(), $form, $schedule, $descForm);
    }

    #[IsGranted('edit', 'schedule')]
    #[Route('/-/schedules/{schedule_e}/delete', name: 'app_backend_schedule_delete_confirmation', methods: ['GET'])]
    public function confirmDelete(#[ValueResolver('schedule_e')] Schedule $schedule): Response
    {
        return $this->render('schedule/confirmation.twig', ['schedule' => $schedule]);
    }

    #[IsGranted('edit', 'schedule')]
    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/schedules/{schedule_e}', name: 'app_backend_schedule_delete', methods: ['DELETE'])]
    public function delete(#[ValueResolver('schedule_e')] Schedule $schedule): Response
    {
        $eventId = $schedule->getEvent()->getId();

        $this->entityManager->remove($schedule);
        $this->entityManager->flush();

        $this->addSuccessMsg('The requested schedule has been deleted.');

        return $this->redirectToRoute('app_backend_event_detail', [
            'event_e' => $this->encodeID($eventId, ObscurityCodec::EVENT),
        ]);
    }

    #[IsGranted('edit', 'schedule')]
    #[Route('/-/schedules/{schedule_e}/export', name: 'app_backend_schedule_export', methods: ['GET'])]
    public function exportSchedule(
        ScheduleTransformerService              $transformerService,
        #[ValueResolver('schedule_e')] Schedule $schedule,
        #[MapQueryParameter] string             $format,
    ): Response
    {
        $formats = ['json', 'xml', 'csv', 'ical'];

        if (!in_array($format, $formats, true)) {
            throw new BadRequestHttpException('Invalid format "'.$format.'" given.');
        }

        $transformer = $transformerService->getTransformer($format);

        try {
            $data = $transformer->transform($schedule, false, true);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $filename = sprintf(
            '%s-%s.%s',
            $schedule->getEvent()->getSlug(),
            $schedule->getSlug(),
            $transformer->getFileExtension()
        );

        return new Response($data, 200, [
            'Content-Type' => $transformer->getContentType(),
            'Content-Disposition' => 'filename="'.$filename.'"',
        ]);
    }

    protected function renderForm(Event $event, FormInterface $form, Schedule $schedule = null, FormInterface $descriptionForm = null): Response
    {
        $timezones = \DateTimeZone::listIdentifiers();
        $descriptionForm = $schedule
            ? $descriptionForm ?? $this->createForm(EventDescriptionType::class, EventDescriptionUpdateDto::fromEvent($schedule))
            : null;

        return $this->render('schedule/form.twig', [
            'event' => $event,
            'timezones' => $timezones,
            'schedule' => $schedule,
            'result' => null,
            'form' => $form,
            'descriptionForm' => $descriptionForm,
            'themes' => $this->getParameter('horaro.themes'),
            'defaultTheme' => $event->getTheme(),
        ]);
    }
}
