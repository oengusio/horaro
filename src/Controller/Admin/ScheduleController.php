<?php

namespace App\Controller\Admin;

use App\Entity\Schedule;
use App\Horaro\Pager;
use App\Horaro\RoleManager;
use App\Horaro\Service\ObscurityCodecService;
use App\Repository\ConfigRepository;
use App\Repository\ScheduleItemRepository;
use App\Repository\ScheduleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class ScheduleController extends BaseController
{
    public function __construct(
        private readonly ScheduleItemRepository $itemRepository,
        private readonly ScheduleRepository     $scheduleRepository,
        RoleManager                             $roleManager,
        ConfigRepository                        $config,
        Security                                $security,
        EntityManagerInterface                  $entityManager,
        ObscurityCodecService                   $obscurityCodec,
    )
    {
        parent::__construct($roleManager, $config, $security, $entityManager, $obscurityCodec);
    }

    #[Route('/-/admin/schedules', name: 'app_admin_schedule_index', methods: ['GET'])]
    public function index(
        #[MapQueryParameter] int    $page = 0,
        #[MapQueryParameter] string $q = '',
    ): Response
    {
        $size = 20;

        if ($page < 0) {
            $page = 0;
        }

        $schedules = $this->scheduleRepository->findFiltered($q, $size, $page * $size);
        $total = $this->scheduleRepository->countFiltered($q);

        foreach ($schedules as $schedule) {
            $schedule->itemCount = $this->itemRepository->countItems($schedule);
        }

        return $this->render('admin/schedules/index.twig', [
            'schedules' => $schedules,
            'pager' => new Pager($page, $total, $size),
            'query' => $q,
        ]);
    }

    #[Route('/-/admin/schedules/{schedule}/edit', name: 'app_admin_schedule_edit', methods: ['GET'])]
    public function editForm(Schedule $schedule): Response {
        if (!$this->canEdit($schedule)) {
            return $this->render('admin/schedules/view.twig', ['schedule' => $schedule]);
        }

        return $this->renderForm($schedule);
    }

    protected function renderForm(Schedule $schedule, array $result = null): Response {
        $itemRepo  = $this->itemRepository;
        $timezones = \DateTimeZone::listIdentifiers();

        $schedule->itemCount = $itemRepo->countItems($schedule);

        return $this->render('admin/schedules/form.twig', [
            'result'    => $result,
            'timezones' => $timezones,
            'themes'    => $this->getParameter('horaro.themes'),
            'schedule'  => $schedule
        ]);
    }

    protected function canEdit(Schedule $schedule): bool {
        return $this->roleManager->canEditSchedule($this->getCurrentUser(), $schedule);
    }
}
