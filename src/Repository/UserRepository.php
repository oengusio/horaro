<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findFiltered(string $query, int $size, int $offset) {
        return $this->createQueryBuilder('u')
                    ->where('u.login LIKE :query')
                    ->orWhere('u.display_name LIKE :query')
                    ->setParameter('query', '%'.$query.'%')
                    ->add('orderBy', 'u.login ASC')
                    ->setMaxResults($size)
                    ->setFirstResult($offset)
                    ->getQuery()
                    ->getResult();
    }

    public function countFiltered(string $query) {
        return $this->createQueryBuilder('u')
                    ->select('COUNT(u)')
                    ->where('u.login LIKE :query')
                    ->orWhere('u.display_name LIKE :query')
                    ->setParameter('query', '%'.$query.'%')
                    ->getQuery()
                    ->getSingleScalarResult();
    }

    /**
     * @return User[]
     */
    public function findInactiveOAuthAccounts(): array {
        // TODO: use query builder
        $dql   = 'SELECT DISTINCT u FROM App\Entity\User u LEFT JOIN u.events e WHERE u.password IS NULL AND e.id IS NULL AND u.twitch_oauth IS NOT NULL AND u.created_at < :threshold ORDER BY u.id ASC';
        $query = $this->getEntityManager()->createQuery($dql);

        $query->setParameter('threshold', gmdate('Y-m-d H:i:s', strtotime('-1 month')));

        return $query->getResult();
    }
}
