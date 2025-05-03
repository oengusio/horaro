<?php

namespace App\Repository;

use App\Entity\Schedule;
use App\Entity\ScheduleItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScheduleItem>
 */
class ScheduleItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduleItem::class);
    }

    public function movePosition(Schedule $schedule, int $posA, int $posB, string $relation)
    {
        if ($relation !== '+' && $relation !== '-') {
            throw new \Exception('Hold the fuck up! How is this possible??');
        }

        $qb = $this->createQueryBuilder('i');

        return $qb->update()
//                    ->set('i.position', 'i.position :relation 1')
                    ->set('i.position', sprintf('i.position %s 1', $relation)) // not very safe, but fine in theory since it is the only way that works
//                    ->where('i.schedule = :schedule')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('i.schedule', $schedule->getId()),
                $qb->expr()->between('i.position', $posA, $posB)
            ))
//                    ->andWhere('i.position BETWEEN :posA AND :posB')
                    ->andWhere($qb->expr()->between('i.position', $posA, $posB))
//                    ->setParameter('schedule', $schedule)
//                    ->setParameter('relation', $relation)
//                    ->setParameter('posA', $posA)
//                    ->setParameter('posB', $posB)
                    ->getQuery()
                    ->getResult();
    }
}
