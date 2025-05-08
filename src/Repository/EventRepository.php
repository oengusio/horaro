<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @param int[] $eventIds
     *
     * @return Event[]
     */
    public function findByIds(array $eventIds): array
    {
        return $this->createQueryBuilder('e')
                    ->andWhere('e.id in (:ids)')
                    ->setParameter('ids', $eventIds)
                    ->getQuery()
                    ->getResult();
    }

    public function findPublic(): array
    {
        return $this->createQueryBuilder('e')
                    ->andWhere('e.secret IS NULL')
                    ->getQuery()
                    ->getResult();
    }

    public function countEvents(User $user = null): int {
        $dql = 'SELECT COUNT(e.id) FROM App\Entity\Event e';

        if ($user) {
            $query = $this->getEntityManager()->createQuery($dql.' WHERE e.user = :user');
            $query->setParameter('user', $user);
        }
        else {
            $query = $this->getEntityManager()->createQuery($dql);
        }

        return (int) $query->getSingleScalarResult();
    }

    public function findFiltered(string $query, int $size, int $offset) {
        return $this->createQueryBuilder('e')
                    ->where('e.name LIKE :query')
                    ->orWhere('e.slug LIKE :query')
                    ->orWhere('e.twitter LIKE :query')
                    ->orWhere('e.twitch LIKE :query')
                    ->orWhere('e.website LIKE :query')
                    ->setParameter('query', '%'.$query.'%')
                    ->add('orderBy', 'e.name ASC')
                    ->setMaxResults($size)
                    ->setFirstResult($offset)
                    ->getQuery()
                    ->getResult();
    }

    public function countFiltered(string $query) {
        return $this->createQueryBuilder('e')
                    ->select('COUNT(e)')
                    ->where('e.name LIKE :query')
                    ->orWhere('e.slug LIKE :query')
                    ->orWhere('e.twitter LIKE :query')
                    ->orWhere('e.twitch LIKE :query')
                    ->orWhere('e.website LIKE :query')
                    ->setParameter('query', '%'.$query.'%')
                    ->getQuery()
                    ->getSingleScalarResult();
    }
}
