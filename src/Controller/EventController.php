<?php

namespace App\Controller;

use App\Entity\Event;
use App\Horaro\DTO\CreateEventDto;
use App\Horaro\DTO\EventDescriptionUpdateDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class EventController extends BaseController
{
    #[Route('/-/events/new', name: 'app_backend_event_new_form', methods: ['GET'])]
    public function showNewForm(): Response {
        if ($this->exceedsMaxEvents($this->getCurrentUser())) {
            return $this->redirect('/-/home');
        }

        return $this->renderForm();
    }

    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/events', name: 'app_backend_event_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateEventDto $createDto,
    ): Response {
        $user = $this->getCurrentUser();

        if ($this->exceedsMaxEvents($user)) {
            return $this->redirect('/-/home');
        }

        $event = new Event();

        $event
            ->setUser($user)
            ->setName($createDto->getName())
            ->setSlug($createDto->getSlug())
            ->setWebsite($createDto->getWebsite())
            ->setTwitter($createDto->getTwitter())
            ->setTwitch($createDto->getTwitch())
            ->setTheme($createDto->getTheme())
            ->setSecret($createDto->getSecret())
            ->setMaxSchedules($this->config->getByKey('max_schedules', 10)->getValue())
        ;

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        // done

        $this->addSuccessMsg('Your new event has been created.');

        return $this->redirect('/-/events/'.$this->encodeID($event->getId(), 'event'));
    }

    #[IsGranted('edit', 'event')]
    #[Route('/-/events/{event_e}', name: 'app_backend_event_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('event_e')] Event $event
    ): Response
    {
        return $this->render('event/detail.twig', [
            'event' => $event,
            'themes' => $this->getParameter('horaro.themes'),
            'isFull' => $this->exceedsMaxSchedules($event),
        ]);
    }

    #[IsGranted('edit', 'event')]
    #[Route('/-/events/{event_e}/edit', name: 'app_backend_event_edit', methods: ['GET'])]
    public function editEventForm(#[ValueResolver('event_e')] Event $event): Response {
        return $this->renderForm($event);
    }

    #[IsGranted('edit', 'event')]
    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/events/{event_e}', name: 'app_backend_event_update', methods: ['PUT'])]
    public function updateEvent(
        #[ValueResolver('event_e')] Event $event,
        #[MapRequestPayload] CreateEventDto $createDto,
    ): Response
    {
        $event
            ->setName($createDto->getName())
            ->setSlug($createDto->getSlug())
            ->setWebsite($createDto->getWebsite())
            ->setTwitter($createDto->getTwitter())
            ->setTwitch($createDto->getTwitch())
            ->setTheme($createDto->getTheme())
            ->setSecret($createDto->getSecret());

        $this->entityManager->flush();

        // done

        $this->addSuccessMsg('Your event has been updated.');

        return $this->redirect('/-/events/'.$this->encodeID($event->getId(), 'event'));
    }

    #[IsGranted('edit', 'event')]
    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/events/{event_e}/description', name: 'app_backend_event_description_update', methods: ['PUT'])]
    public function updateDescription(
        #[ValueResolver('event_e')] Event $event,
        #[MapRequestPayload] EventDescriptionUpdateDto $dto,
    ): Response
    {
        $event->setDescription($dto->getDescription());
        $this->entityManager->flush();

        // done

        $this->addSuccessMsg('Your event description has been updated.');

        return $this->redirect('/-/events/'.$this->encodeID($event->getId(), 'event'));

    }

    #[IsGranted('edit', 'event')]
    #[Route('/-/events/{event_e}/delete', name: 'app_backend_event_delete_form', methods: ['GET'])]
    public function confirmDelete(#[ValueResolver('event_e')] Event $event): Response {
        return $this->render('event/confirmation.twig', ['event' => $event]);
    }

    #[IsGranted('edit', 'event')]
    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/events/{event_e}', name: 'app_backend_event_kill_it', methods: ['DELETE'])]
    public function deleteEvent(#[ValueResolver('event_e')] Event $event): Response {
        $this->entityManager->remove($event);
        $this->entityManager->flush();

        $this->addSuccessMsg('The requested event has been deleted.');

        return $this->redirect('/-/home');
    }

    protected function renderForm(?Event $event = null, mixed $result = null) {
        return $this->render('event/form.twig', [
            'event'        => $event,
            'result'       => $result,
            'themes'       => $this->getParameter('horaro.themes'),
            'defaultTheme' => $this->config->getByKey('default_event_theme', 'yeti')->getValue(),
        ]);
    }
}
