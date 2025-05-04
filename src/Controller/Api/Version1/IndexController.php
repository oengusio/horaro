<?php

namespace App\Controller\Api\Version1;

use App\Controller\Api\BaseController;
use App\Horaro\Transformer\Version1\IndexTransformer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends BaseController
{
    #[Route('/-/api/v1', name: 'app_api_v1_index', methods: ['GET'], priority: 1)]
    public function index(): JsonResponse
    {
        return $this->respondWithItem(null, new IndexTransformer($this->requestStack, $this->obscurityCodec));
    }
}
