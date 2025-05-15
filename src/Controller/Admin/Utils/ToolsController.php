<?php

namespace App\Controller\Admin\Utils;

use App\Controller\Admin\BaseController;
use App\Entity\Schedule;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_OP')]
class ToolsController extends BaseController
{
    #[Route('/-/admin/utils/tools', name: 'app_op_tools_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/utils/tools.twig');
    }

    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/admin/utils/tools/cleartwigcache', name: 'app_op_tools_clear_twig_cache', methods: ['POST'])]
    public function clearTwigCache(): Response
    {
        // Default value, need to see if I can get access to the actual value
        $twigCacheDir = $this->getParameter('kernel.cache_dir').'/twig';

        if (!is_dir($twigCacheDir)) {
            $this->addErrorMsg('Could not find Twig cache.');

            return $this->redirect('/-/admin/utils/tools');
        }

        $files = [];
        $finder = new Finder();
        $finder->in($twigCacheDir);

        foreach ($finder as $file) {
            $files[] = (string) $file;
        }

        foreach (array_reverse($files) as $file) {
            if (is_file($file)) {
                unlink($file);
            } else {
                rmdir($file);
            }
        }

        $this->addSuccessMsg('The Twig cache has been cleared.');

        return $this->redirect('/-/admin/utils/tools');
    }

    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/admin/utils/tools/fixpositions', name: 'app_op_tools_fix_positions', methods: ['POST'])]
    public function fixSchedulePositions(): Response
    {
        $scheduleRepo = $this->entityManager->getRepository(Schedule::class);
        /** @var Schedule[] $schedules */
        $schedules = $scheduleRepo->findAll();

        // Let's get funky :)
        $conn = $this->entityManager->getConnection();

        foreach ($schedules as $schedule) {
            $scheduleID = $schedule->getId();

            $conn->executeQuery('SET @pos = 0;');
            $conn->executeStatement('UPDATE schedule_items SET position := (@pos := @pos + 1) WHERE schedule_id = ? ORDER BY position', [$scheduleID]);

            $conn->executeQuery('SET @pos = 0;');
            $conn->executeStatement('UPDATE schedule_columns SET position := (@pos := @pos + 1) WHERE schedule_id = ? ORDER BY position', [$scheduleID]);
        }

        $this->addSuccessMsg('The items have been re-numbered.');

        return $this->redirect('/-/admin/utils/tools');
    }

    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/admin/utils/tools/cleanupusers', name: 'app_op_tools_fix_positions', methods: ['POST'])]
    public function cleanupUsers(UserRepository $repo): Response
    {
        $users = $repo->findInactiveOAuthAccounts();
        $em = $this->entityManager;

        $em->wrapInTransaction(function(EntityManagerInterface $em) use ($users) {
            foreach ($users as $user) {
                $em->remove($user);
            }
        });

        $num = count($users);
        $this->addSuccessMsg(sprintf('%d account%s have been removed.', $num, $num == 1 ? '' : 's'));

        return $this->redirect('/-/admin/utils/tools');
    }
}
