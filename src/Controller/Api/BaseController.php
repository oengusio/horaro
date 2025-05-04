<?php

namespace App\Controller\Api;


use App\Horaro\Pager\PagerInterface;
use App\Horaro\Service\FractalService;
use App\Horaro\Service\ObscurityCodecService;
use App\JsonRedirectResponse;
use App\Repository\ConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Fractal\Resource;
use League\Fractal\Resource\ResourceInterface;
use League\Fractal\TransformerAbstract;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BaseController extends \App\Controller\BaseController
{
    public function __construct(
        private readonly FractalService $fractal,
        private readonly RequestStack $requestStack,
        ConfigRepository $config,
        Security $security,
        EntityManagerInterface $entityManager,
        ObscurityCodecService $obscurityCodec,
    )
    {
        parent::__construct($config, $security, $entityManager, $obscurityCodec);
    }

    protected function getFractalManager(): \League\Fractal\Manager
    {
        return $this->fractal->getManager();
    }

    protected function hasResourceAccess($resource) {
        return $resource->isPublic();
    }

    protected function transform(ResourceInterface $resource): array {
        $fractal   = $this->getFractalManager();
        $rootScope = $fractal->createData($resource);

        return $rootScope->toArray();
    }

    protected function respondWithCollection($collection, TransformerAbstract $transformer, PagerInterface $pager = null, $dataKey = 'data', $status = 200): JsonResponse
    {
        $collection = new Resource\Collection($collection, $transformer, $dataKey);
        $data       = $this->transform($collection);

        if ($pager) {
            $pager->setCurrentCollection($collection);
            $data['pagination'] = $pager->createData();
        }

        return $this->respondWithArray($data, $status);
    }

    protected function respondWithItem($item, TransformerAbstract $transformer, $dataKey = 'data', $status = 200): JsonResponse
    {
        $data = $this->transform(new Resource\Item($item, $transformer, $dataKey));

        return $this->respondWithArray($data, $status);
    }

    protected function respondWithArray($content = [], $status = 200, array $headers = []): JsonResponse
    {
        $request = $this->requestStack->getCurrentRequest();

        $response = new JsonResponse($content, $status, $headers);
        $response->setEncodingOptions($response->getEncodingOptions() | JSON_UNESCAPED_SLASHES);

        if ($request->query->has('callback')) {
            try {
                $response->setCallback($request->query->get('callback'));
            }
            catch (\Exception $e) {
                throw new BadRequestHttpException($e->getMessage());
            }
        }

        $response->setExpires(new \DateTime('1924-10-10 12:00:00 UTC'));
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->addCacheControlDirective('private', true);

        return $response;
    }

    /*protected function redirect(string $url, int $status = 302)
    {
        return new JsonRedirectResponse($url, $status);
    }*/
}
