<?php

namespace App\Repository;

use App\Entity\ItemQuality;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ItemQuality>
 *
 * @method ItemQuality|null find($id, $lockMode = null, $lockVersion = null)
 * @method ItemQuality|null findOneBy(array $criteria, array $orderBy = null)
 * @method ItemQuality[]    findAll()
 * @method ItemQuality[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemQualityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemQuality::class);
    }

    //    /**
    //     * @return ItemQuality[] Returns an array of ItemQuality objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('i.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ItemQuality
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
