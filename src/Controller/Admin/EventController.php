<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Horaro\DTO\Admin\UpdateEventDto;
use App\Horaro\Pager;
use App\Horaro\RoleManager;
use App\Horaro\Service\ObscurityCodecService;
use App\Repository\ConfigRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class EventController extends BaseController
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        RoleManager $roleManager,
        ConfigRepository $config,
        Security $security,
        EntityManagerInterface $entityManager,
        ObscurityCodecService $obscurityCodec,
    )
    {
        parent::__construct($roleManager, $config, $security, $entityManager, $obscurityCodec);
    }

    #[Route('/-/admin/events', name: 'app_admin_event_index', methods: ['GET'])]
    public function index(
        #[MapQueryParameter] int    $page = 0,
        #[MapQueryParameter] string $q = '',
    ): Response
    {
        $size = 20;

        if ($page < 0) {
            $page = 0;
        }

        $query     = $q;
        $events    = $this->eventRepository->findFiltered($query, $size, $page*$size);
        $total     = $this->eventRepository->countFiltered($query);

        return $this->render('admin/events/index.twig', [
            'events' => $events,
            'pager'  => new Pager($page, $total, $size),
            'query'  => $query
        ]);
    }

    #[Route('/-/admin/events/{event}/edit', name: 'app_admin_event_edit', methods: ['GET'])]
    public function edit(Event $event): Response
    {
        if (!$this->canEdit($event)) {
            return $this->render('admin/events/view.twig', [
                'event' => $event,
                'themes'   => $this->getParameter('horaro.themes'),
            ]);
        }

        return $this->renderForm($event);
    }

    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/admin/events/{event}', name: 'app_admin_event_update', methods: ['PUT'])]
    public function update(
        Event $event,
        #[MapRequestPayload] UpdateEventDto $dto,
    ): Response {
        if (!$this->canEdit($event)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this event.');
        }

        $event
            ->setName($dto->getName())
            ->setSlug($dto->getSlug())
            ->setWebsite($dto->getWebsite())
            ->setTwitter($dto->getTwitter())
            ->setTwitch($dto->getTwitch())
            ->setTheme($dto->getTheme())
            ->setMaxSchedules($dto->getMaxSchedules());

        $eventID  = $event->getID();
        $featuredModel = $this->config->getByKey('featured_events', []);
        $featuredValue = $featuredModel->getValue();

        if ($dto->isFeatured()) {
            if (!in_array($eventID, $featuredValue)) {
                $featuredValue[] = $eventID;
                sort($featuredValue);
            }
        } else if (($pos = array_search($eventID, $featuredValue)) !== false) {
            unset($featuredValue[$pos]);
            $featuredValue = array_values($featuredValue);
        }

        $featuredModel->setValue($featuredValue);

        $this->entityManager->flush();
        $this->addSuccessMsg('Event '.$event->getName().' has been updated.');

        return $this->redirect('/-/admin/events');
    }

    protected function renderForm(Event $event, array $result = null): Response {
        $featured = $this->config->getByKey('featured_events', [])->getValue();

        return $this->render('admin/events/form.twig', [
            'result'   => $result,
            'event'    => $event,
            'themes'   => $this->getParameter('horaro.themes'),
            'featured' => in_array($event->getID(), $featured)
        ]);
    }

    protected function canEdit(Event $event): bool {
        return $this->roleManager->canEditEvent($this->getCurrentUser(), $event);
    }
}
