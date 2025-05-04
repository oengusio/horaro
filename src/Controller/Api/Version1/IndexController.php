<?php

namespace App\Controller\Api\Version1;

use App\Controller\Api\BaseController;
use App\Horaro\Service\ObscurityCodecService;
use App\Horaro\Transformer\Version1\IndexTransformer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends BaseController
{
    #[Route('/-/api/v1', name: 'app_api_v1_index', methods: ['GET'], priority: 1)]
    public function index(Request $request, ObscurityCodecService $obscurityCodecService): \Symfony\Component\HttpFoundation\JsonResponse
    {
        return $this->respondWithItem(null, new IndexTransformer($request, $obscurityCodecService));
    }
}
