<?php

namespace App\Repository;

use App\Entity\Config;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function array_map;

/**
 * @extends ServiceEntityRepository<Config>
 */
class ConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Config::class);
    }

    public function getByKey(string $key, mixed $defaultValue = null): Config {
        return $this->createQueryBuilder('c')
            ->andWhere('c.keyname = :key')
            ->setParameter('key', $key)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult() ?? new Config($key, $defaultValue);
    }

    public function getAll(): array {
        /** @var Config[] $allConfig */
        $allConfig = $this->findAll();

        // Can't use array_map sadly because we need a "flat" array
        // There *might* be a better way, but ICBA to google that right now
        $result = [];

        foreach ($allConfig as $config) {
            $result[$config->getKeyname()] = $config->getValue();
        }

        /*$mappedConfig = array_map(
            fn (Config $config) => [ ($config->getKeyname()) => $config->getValue() ],
            $allConfig
        );

        dd($mappedConfig);*/

        return $result;
    }

    public function saveBatch(array $newConfig): void {
        /** @var Config[] $allConfig */
        $allConfig = $this->findAll();

        foreach ($allConfig as $config) {
            $newValue = $newConfig[$config->getKeyname()] ?? $config->getValue();

            $config->setValue($newValue);
        }

        $this->getEntityManager()->flush();
    }

    //    /**
    //     * @return Config[] Returns an array of Config objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Config
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
