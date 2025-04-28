<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class HomeController extends BaseController
{
    #[Route('/-/home', name: 'app_home', methods: ['GET'], priority: 1)]
    public function index(Request $request): Response
    {
        $request->getSession()->set('navbar', 'regular'); // options: regular, admin

        return $this->render('home/home.twig', [
            'isFull' => $this->exceedsMaxEvents($this->getCurrentUser()),
        ]);
    }
}
