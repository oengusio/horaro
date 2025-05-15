<?php

namespace App\Controller\Admin\Utils;

use App\Controller\Admin\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_OP')]
class ServerInfoController extends BaseController
{
    #[Route('/-/admin/utils/serverinfo', name: 'app_op_utils_serverinfo', methods: ['GET'])]
    public function form(): Response {
        return $this->render('admin/utils/serverinfo.twig', [
            'phpversion' => PHP_VERSION,
            'root'       => HORARO_ROOT,
            // 'config'     => $this->app['config'], // Not sure why this is here
            'hasPhpinfo' => function_exists('phpinfo'),
        ]);
    }

    #[Route('/-/admin/utils/serverinfo/phpinfo', name: 'app_op_utils_serverinfo_phpinfo', methods: ['GET'])]
    public function phpInfoRenderer() {
        if (function_exists('phpinfo')) {
            phpinfo();
            die;
        }

        return new Response('phpinfo() is disabled.', 501);
    }
}
