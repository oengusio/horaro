<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class IndexController extends BaseController
{
    #[Route('/-/admin', name: 'app_admin_index')]
    public function dashboard(Request $request): Response
    {
        $request->getSession()->set('navbar', 'admin');

        return $this->render('admin/dashboard.twig');
    }
}
