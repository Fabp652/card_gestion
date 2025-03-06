<?php

namespace App\Repository;

use App\Entity\ItemSale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ItemSale>
 *
 * @method ItemSale|null find($id, $lockMode = null, $lockVersion = null)
 * @method ItemSale|null findOneBy(array $criteria, array $orderBy = null)
 * @method ItemSale[]    findAll()
 * @method ItemSale[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemSaleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemSale::class);
    }

//    /**
//     * @return ItemSale[] Returns an array of ItemSale objects
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

//    public function findOneBySomeField($value): ?ItemSale
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
