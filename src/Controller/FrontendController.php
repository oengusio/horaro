<?php

namespace App\Controller;

use App\Entity\Event;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FrontendController extends AbstractController
{
    #[Route('/{eventSlug}', name: 'app_frontend_event_home')]
    public function event(
        #[MapEntity(mapping: ['eventSlug' => 'slug'])] Event $event
    ): Response
    {
        return $this->render('frontend/event/event.twig', [
            'event'       => $event,
            'key'         => '',// $key,
            'schedules'   => [], //$this->getAllowedSchedules($event, $key),
            'description' => '', //$description,
            'isPrivate'   => false, //$isPrivate,
        ]);
    }
}
