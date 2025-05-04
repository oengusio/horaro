<?php

namespace App\Repository;

use App\Entity\Schedule;
use App\Entity\ScheduleColumn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScheduleColumn>
 */
class ScheduleColumnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduleColumn::class);
    }

    public function movePosition(Schedule $schedule, int $posA, int $posB, string $relation)
    {
        // Sanity checking
        if ($relation !== '+' && $relation !== '-') {
            throw new \RuntimeException('Hold the fuck up! How is this possible??');
        }

        $qb = $this->createQueryBuilder('c');

        return $qb->update()
                  ->set('c.position', sprintf('c.position %s 1', $relation))
                  ->where('c.schedule = :schedule')
                  ->andWhere('c.position BETWEEN :posA AND :posB')
                  ->setParameter('schedule', $schedule)
                  ->setParameter('posA', $posA)
                  ->setParameter('posB', $posB)
                  ->getQuery()
                  ->getResult();
    }

    public function movePreDelOnePositionUp(Schedule $schedule, int $oldItemPosition)
    {
        $qb = $this->createQueryBuilder('c');

        return $qb->update()
                  ->set('c.position', 'c.position - 1')
                  ->where('c.schedule = :schedule')
                  ->andWhere('c.position > :oldPos')
                  ->setParameter('schedule', $schedule)
                  ->setParameter('oldPos', $oldItemPosition)
                  ->getQuery()
                  ->getResult();
    }
}
