<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Horaro\DTO\Admin\UpdateUserDto;
use App\Horaro\Pager;
use App\Horaro\RoleManager;
use App\Horaro\Service\ObscurityCodecService;
use App\Repository\ConfigRepository;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
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
final class UserController extends BaseController
{
    public function __construct(
        private readonly UserRepository  $userRepository,
        private readonly EventRepository $eventRepository,
        RoleManager                      $roleManager,
        ConfigRepository                 $config,
        Security                         $security,
        EntityManagerInterface           $entityManager,
        ObscurityCodecService            $obscurityCodec,
    )
    {
        parent::__construct($roleManager, $config, $security, $entityManager, $obscurityCodec);
    }

    #[Route('/-/admin/users', name: 'app_admin_user', methods: ['GET'])]
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
        $eventRepo = $this->eventRepository;
        $userRepo  = $this->userRepository;
        $users     = $userRepo->findFiltered($query, $size, $page*$size);
        $total     = $userRepo->countFiltered($query);

        foreach ($users as $user) {
            $user->eventCount = $eventRepo->countEvents($user);
        }

        return $this->render('admin/users/index.twig', [
            'users' => $users,
            'pager' => new Pager($page, $total, $size),
            'query'     => $query
        ]);
    }

    #[Route('/-/admin/users/{user}/edit', name: 'app_admin_user_edit', methods: ['GET'])]
    public function editUser(User $user): Response {
        if (!$this->canEdit($user)) {
            return $this->render('admin/users/view.twig', ['user' => $user, 'languages' => $this->getLanguages()]);
        }

        return $this->renderForm($user);
    }

    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/admin/users/{user}', name: 'app_admin_user_save', methods: ['PUT'])]
    public function saveUser(
        User $user,
        #[MapRequestPayload] UpdateUserDto $dto,
    ): Response {
        if (!$this->canEdit($user)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this user.');
        }

        $user->setLogin(strtolower($dto->getLogin()));
        $user->setDisplayName($dto->getDisplayName());
        $user->setLanguage($dto->getLanguage());
        $user->setGravatarHash($dto->getGravatar());
        $user->setMaxEvents($dto->getMaxEvents());
        $user->setRole($dto->getRole());

        $this->entityManager->flush();

        $this->addSuccessMsg('User '.$user->getLogin().' has been updated.');

        return $this->redirect('/-/admin/users');
    }

    // TODO: update password

    protected function renderForm(User $user, array $result = null): Response
    {
        return $this->render('admin/users/form.twig', [
            'result'    => $result,
            'user'      => $user,
            'languages' => $this->getLanguages()
        ]);
    }

    protected function canEdit(User $user): bool
    {
        return $this->roleManager->canEditUser($this->getCurrentUser(), $user);
    }
}
