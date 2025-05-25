<?php

namespace App\Controller\Admin;

use App\Entity\Schedule;
use App\Form\Type\Admin\ScheduleType;
use App\Horaro\DTO\Admin\UpdateScheduleDto;
use App\Horaro\Pager;
use App\Horaro\RoleManager;
use App\Horaro\Service\ObscurityCodecService;
use App\Repository\ConfigRepository;
use App\Repository\ScheduleItemRepository;
use App\Repository\ScheduleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
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

    #[Route('/-/admin/schedules/{schedule}/edit', name: 'app_admin_schedule_edit', methods: ['GET', 'PUT'])]
    public function editForm(Request $request, Schedule $schedule): Response
    {
        if (!$this->canEdit($schedule)) {
            return $this->render('admin/schedules/view.twig', ['schedule' => $schedule]);
        }

        $dto = UpdateScheduleDto::fromSchedule($schedule);
        $form = $this->createForm(ScheduleType::class, $dto, [
            'method' => 'PUT',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dtoStartDate = $dto->getStartDate();
            $dtoStartTime = $dto->getStartTime();
            $startDateTime = \DateTime::createFromFormat('Y-m-d G:i', "$dtoStartDate $dtoStartTime");

            $schedule
                ->setName($dto->getName())
                ->setSlug($dto->getSlug())
                ->setTimezone($dto->getTimezone())
                ->setStart($startDateTime)
                ->setWebsite($dto->getWebsite())
                ->setTwitter($dto->getTwitter())
                ->setTwitch($dto->getTwitch())
                ->setTheme($dto->getTheme())
                ->setSecret($dto->getSecret())
                ->setMaxItems($dto->getMaxItems())
                ->touch();

            $this->entityManager->flush();

            $this->addSuccessMsg('Schedule '.$schedule->getName().' has been updated.');

            return $this->redirectToRoute('app_admin_schedule_index');
        }

        return $this->renderForm($schedule, $form);
    }

    #[Route('/-/admin/schedules/{schedule}/delete', name: 'app_admin_schedule_delete_form', methods: ['GET'])]
    public function deleteScheduleForm(Schedule $schedule): Response
    {
        if (!$this->canEdit($schedule)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this schedule.');
        }

        return $this->render('admin/schedules/confirmation.twig', ['schedule' => $schedule]);
    }

    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/admin/schedules/{schedule}', name: 'app_admin_schedule_delete', methods: ['DELETE'])]
    public function goGoGadgetDeleteIt(Schedule $schedule): Response
    {
        if (!$this->canEdit($schedule)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this schedule.');
        }

        $this->entityManager->remove($schedule);
        $this->entityManager->flush();

        $this->addSuccessMsg('The requested schedule has been deleted.');

        return $this->redirectToRoute('app_admin_schedule_index');
    }

    protected function renderForm(Schedule $schedule, FormInterface $form): Response
    {
        $timezones = \DateTimeZone::listIdentifiers();

        $schedule->itemCount = $this->itemRepository->countItems($schedule);

        return $this->render('admin/schedules/form.twig', [
            'result' => null,
            'form' => $form,
            'timezones' => $timezones,
            'themes' => $this->getParameter('horaro.themes'),
            'schedule' => $schedule,
        ]);
    }

    protected function canEdit(Schedule $schedule): bool
    {
        return $this->roleManager->canEditSchedule($this->getCurrentUser(), $schedule);
    }
}
