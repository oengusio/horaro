<?php

namespace App\Controller\Admin\Utils;

use App\Controller\Admin\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_OP')]
class IndexController extends BaseController
{
    #[Route('/-/admin/utils', name: 'app_op_utils_index', methods: ['GET'])]
    public function index(): Response {
        return $this->redirect('/-/admin/utils/config');
    }
}
