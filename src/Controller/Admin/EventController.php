<?php

namespace App\Controller\Admin;

use App\Horaro\Pager;
use App\Horaro\RoleManager;
use App\Horaro\Service\ObscurityCodecService;
use App\Repository\ConfigRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
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

    #[Route('/-/admin/events', name: 'app_admin_event_index')]
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
}
