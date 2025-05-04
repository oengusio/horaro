<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IndexController extends BaseController
{
    #[Route('/-/api', name: 'app_api_index', methods: ['GET'], priority: 1)]
    public function index(Request $request): Response
    {
        $html = $this->render('index/api.twig', [
            'baseUri' => $request->getUriForPath('')
        ]);

        return $this->setCachingHeader($html, 'other');
    }
}
