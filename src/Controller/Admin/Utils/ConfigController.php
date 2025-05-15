<?php

namespace App\Controller\Admin\Utils;

use App\Controller\Admin\BaseController;
use App\Entity\Config;
use App\Horaro\DTO\Admin\ConfigUpdateDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_OP')]
class ConfigController extends BaseController
{
    #[Route('/-/admin/utils/config', name: 'app_op_utils_config', methods: ['GET'])]
    public function index(): Response {
        return $this->renderForm($this->config->getAll());
    }

    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/admin/utils/config', name: 'app_op_utils_config_save', methods: ['PUT'])]
    public function save(#[MapRequestPayload] ConfigUpdateDto $dto): Response {
        $this->config->saveBatch([
            'bcrypt_cost' => $dto->getBcryptCost(),
            'cookie_lifetime' => $dto->getCookieLifetime(),
            'csrf_token_name' => $dto->getCsrfTokenName(),
            'default_event_theme' => $dto->getDefaultEventTheme(),
            'default_language' => $dto->getDefaultLanguage(),
            'max_events' => $dto->getMaxEvents(),
            'max_schedule_items' => $dto->getMaxScheduleItems(),
            'max_schedules' => $dto->getMaxSchedules(),
            'max_users' => $dto->getMaxUsers(),
            'sentry_dsn' => $dto->getSentryDsn(),
        ]);

        $this->addSuccessMsg('The configuration has been updated.');

        return $this->redirect('/-/admin/utils/config');
    }

    protected function renderForm(Config|array $config = null, $result = null) {
        return $this->render('admin/utils/config.twig', [
            'config'    => $config,
            'languages' => $this->getLanguages(),
            'result'    => $result,
            'themes' => $this->getParameter('horaro.themes'),
        ]);
    }
}
