<?php

namespace App\Repository;

use App\Entity\Schedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Schedule>
 */
class ScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Schedule::class);
    }

    /**
     * @return Schedule[]
     */
    public function findCurrentlyRunning(): array
    {
        $day = 24 * 3600;
        $now = time();
        /** @var Schedule[] $schedules */
        $schedules = $this->findPublicInRange($now - 21 * $day, $now + 1 * $day);
        $result = [];

        foreach ($schedules as $schedule) {
            $start = $schedule->getLocalStart()->format('U');

            if ($start < $now) {
                $end = $schedule->getLocalEnd()->format('U'); // defer calculating this until we checked the start

                if ($end > $now) {
                    $result[] = $schedule;
                }
            }
        }

        return $result;
    }

    /**
     * @param int $days
     *
     * @return Schedule[]
     */
    public function findUpcoming(int $days): array {
        // search begins at "now minus 1 day" to include events with different timezones as well;
        // this requires filtering the schedules later on by their actual start date/time.

        $day       = 24 * 3600;
        $now       = time();
        $schedules = $this->findPublicInRange($now - 1*$day, $now + $days*$day);
        $result    = [];

        foreach ($schedules as $schedule) {
            $start = $schedule->getLocalStart()->format('U');

            if ($start > $now) {
                $result[] = $schedule;
            }
        }

        return $result;
    }

    protected function findPublicInRange($from, $to)
    {
        $query = $this->createQueryBuilder('s')
                      ->join('s.event', 'e')
                      ->andWhere('e.secret is null')
                      ->andWhere('s.secret is null')
                      ->andWhere('s.start BETWEEN :from AND :to')
                      ->orderBy('s.start', 'ASC');

        $query->setParameter('from', gmdate('Y-m-d H:i:s', $from));
        $query->setParameter('to', gmdate('Y-m-d H:i:s', $to));

        return $query->getQuery()->getResult();
    }

    //    /**
    //     * @return Schedule[] Returns an array of Schedule objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Schedule
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
