<?php

namespace App\Controller;

use App\Entity\Event;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EventController extends AbstractController
{
    #[Route('/-/events/{event_e}', name: 'app_backend_event_detail')]
    public function detail(
        #[MapEntity(mapping: ['event_e' => 'id'])] Event $event
    ): Response
    {

        return $this->render('event/detail.twig', [
            'event' => $event,
            'themes' => [],
            'isFull' => false,
        ]);
    }
}
