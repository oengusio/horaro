<?php

namespace App\Repository;

use App\Entity\Schedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
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

    public function findPublic(\DateTime $startFrom, \DateTime $startTo) {
        return $this->findPublicInRange($startFrom->format('U'), $startTo->format('U'));
    }

    protected function findPublicInRange(string|int $from, string|int $to)
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

    public function findBySlug(string $eventSlug, string $scheduleSlug): ?Schedule
    {
        return $this->createQueryBuilder('s')
            ->join('s.event', 'e')
                    ->andWhere('s.slug = :scheduleSlug')
                    ->andWhere('e.slug = :eventSlug')
                    ->setParameter('scheduleSlug', $scheduleSlug)
                    ->setParameter('eventSlug', $eventSlug)
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    public function transientLock(Schedule $schedule): void {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Schedule::class, 's');

        $query = $this->getEntityManager()->createNativeQuery(
            'SELECT id FROM schedules WHERE id = :id FOR UPDATE',
            $rsm,
        );
        $query->setParameter('id', $schedule->getId());

        $query->getOneOrNullResult(); // this one blocks until the lock is available
    }
}
