<?php

namespace App\Controller;

use App\Entity\Schedule;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ScheduleImportController extends BaseController
{
    #[IsGranted('edit', 'schedule')]
    #[Route('/-/schedules/{schedule_e}/import', name: 'app_schedule_import', methods: ['GET'])]
    public function importForm(#[ValueResolver('schedule_e')] Schedule $schedule): Response
    {
        return $this->renderForm($schedule);
    }

    protected function renderForm(Schedule $schedule, array $result = null) {
        return $this->render('schedule/import.twig', [
            'schedule' => $schedule,
            'result'   => $result,
            'max_size' => floor(UploadedFile::getMaxFilesize() / 1024)
        ]);
    }
}
