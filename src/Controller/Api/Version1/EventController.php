<?php

namespace App\Controller\Api\Version1;

use App\Controller\Api\BaseController;
use App\Entity\Event;
use App\Horaro\Pager\OffsetLimitPager;
use App\Horaro\Transformer\Version1\EventTransformer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class EventController extends BaseController
{
    #[Route('/-/api/v1/events', name: 'app_api_v1_event_list', methods: ['GET'])]
    public function listPublicEvents(Request $request)
    {
        // determine current page
        $pager = new OffsetLimitPager($request);
        $offset = $pager->getOffset();
        $size = $pager->getPageSize();

        // determine direction
        $allowed = ['name' => 'e.name'];
        $orderBy = $pager->getOrder(array_keys($allowed), 'name');
        $direction = $pager->getDirection('ASC');
        $orderBy = $allowed[$orderBy];

        // prepare query builder
        $queryBuilder = $this->entityManager->getRepository(Event::class)
                                            ->createQueryBuilder('e')
                                            ->where('e.secret IS NULL')
                                            ->orderBy($orderBy, $direction)
                                            ->setFirstResult($offset)
                                            ->setMaxResults($size);

        // filter by name
        $name = trim($request->query->get('name'));

        if (mb_strlen($name) > 0) {
            $queryBuilder
                ->andWhere('e.name LIKE :name')
                ->setParameter('name', '%'.addcslashes($name, '%_').'%');
        }

        // find events
        $events = $queryBuilder->getQuery()->getResult();

        $transformer = new EventTransformer($this->requestStack, $this->obscurityCodec);
        return $this->respondWithCollection($events, $transformer, $pager);
    }

}
