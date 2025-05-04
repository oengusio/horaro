<?php

namespace App\Controller;

use App\Entity\Schedule;
use App\Horaro\ScheduleImporter\JsonImporter;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function str_starts_with;
use function preg_match;
use function in_array;

#[IsGranted('ROLE_USER')]
final class ScheduleImportController extends BaseController
{
    #[IsGranted('edit', 'schedule')]
    #[Route('/-/schedules/{schedule_e}/import', name: 'app_schedule_import', methods: ['GET'])]
    public function importForm(#[ValueResolver('schedule_e')] Schedule $schedule): Response
    {
        return $this->renderForm($schedule);
    }

    #[IsGranted('edit', 'schedule')]
    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/schedules/{schedule_e}/import', name: 'app_schedule_import_submit', methods: ['POST'])]
    public function importAction(
        Request $request,
        JsonImporter $jsonImporter,
        #[ValueResolver('schedule_e')] Schedule $schedule,
    ): Response
    {
        $validateResult = ['_errors' => false];

        $upload = $request->files->get('file');

        // do not compare with "if (!$upload)", because of PHP bug #65213
        if (!($upload instanceof UploadedFile)) {
            $validateResult['_errors'] = true;
            $validateResult['file']['errors'] = true;
            $validateResult['file']['messages'][] = 'No file was uploaded.';


            return $this->renderForm($schedule, $validateResult);
        }

        if (!$upload->isValid()) {
            $validateResult['_errors'] = true;
            $validateResult['file']['errors'] = true;
            $validateResult['file']['messages'][] = $this->getUploadErrorMessage($upload);
        }

        $fileType = $this->getImportType($upload);

        if ($fileType === null) {
            $validateResult['_errors'] = true;
            $validateResult['file']['errors'] = true;
            $validateResult['file']['messages'][] = 'Could not recognize the file format.';
        }

        if ($validateResult['_errors']) {
            return $this->renderForm($schedule, $validateResult);
        }

        $filePath = (string) $upload;
        $ignoreErrors   = !!$request->request->get('ignore');
        $updateMetadata = !!$request->request->get('metadata');

        $importer = match ($fileType) {
            'json' => $jsonImporter,
            default => throw new \RuntimeException('Invalid importer'),
        };
        try {
            $log = $importer->import($filePath, $schedule, $ignoreErrors, $updateMetadata);
        }
        catch (\Exception $e) {
            $log = $e;
        }

        $hasErrors = false;

        foreach ($log as $row) {
            if ($row[0] === 'error') {
                $hasErrors = true;
                break;
            }
        }

        // respond

        return $this->render('schedule/import-result.twig', [
            'schedule' => $schedule,
            'log'      => $log,
            'errors'   => $hasErrors,
            'stopped'  => $hasErrors && !$ignoreErrors,
            'failed'   => $log instanceof \Exception,
            'upload'   => $upload,
        ]);
    }

    protected function renderForm(Schedule $schedule, array $result = null) {
        return $this->render('schedule/import.twig', [
            'schedule' => $schedule,
            'result'   => $result,
            'max_size' => floor(UploadedFile::getMaxFilesize() / 1024)
        ]);
    }

    /**
     * Better than Symfony's version. No babbling about "upload_max_filesize ini directive" and stuff.
     */
    private function getUploadErrorMessage(UploadedFile $upload): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'The file "%s" exceeds the upload limit.',
            UPLOAD_ERR_PARTIAL    => 'The file "%s" was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_CANT_WRITE => 'The file "%s" could not be written on disk.',
            UPLOAD_ERR_NO_TMP_DIR => 'File could not be uploaded: missing temporary directory.',
            UPLOAD_ERR_EXTENSION  => 'File upload was stopped by a PHP extension.'
        ];

        $errorCode = $upload->getError();
        $message   = $errors[$errorCode] ?? 'The file "%s" was not uploaded due to an unknown error.';
        $filename  = $upload->getClientOriginalName();

        if (mb_strlen($filename) > 50) {
            $filename = mb_substr($filename, 0, 50).'…';
        }

        return sprintf($message, $filename);
    }

    private function getImportType(UploadedFile $file): ?string
    {
        $mime = $file->getMimeType();

        // hooray if the mime magic on this system was actually this specific
        if (preg_match('#^(text/json|application/json)#', $mime)) return 'json';
        if (str_starts_with($mime, 'text/csv')) return 'csv';

        // we at least need something that resembles text
        if (!str_starts_with($mime, 'text/')) {
            return null;
        }

        // go by the file extension
        $ext = $file->getClientOriginalExtension();

        return in_array($ext, ['csv', 'json'], true) ? $ext : null;
    }
}
