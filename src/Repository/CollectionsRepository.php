<?php

namespace App\Repository;

use App\Entity\Collections;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Collections>
 *
 * @method Collections|null find($id, $lockMode = null, $lockVersion = null)
 * @method Collections|null findOneBy(array $criteria, array $orderBy = null)
 * @method Collections[]    findAll()
 * @method Collections[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CollectionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Collections::class);
    }

    /**
     * @return array
     */
    public function stats(): array
    {
        return $this->createQueryBuilder('c')
            ->select(
                '
                    SUM(i.price * i.number) AS totalAmount,
                    CASE WHEN COUNT(i.id) > 0 THEN SUM(i.number) ELSE 0 END AS totalItem,
                    c.name AS collectionName,
                    c.id AS collectionId,
                    SUM(i.price * i.number) / SUM(i.number) AS average,
                    cat.name As category
                '
            )
            ->leftJoin('c.items', 'i')
            ->leftJoin('c.category', 'cat')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param int $collectionId
     * @return array
     */
    public function findCollectionsWithoutActual(int $collectionId): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id, c.name')
            ->where('c.id != :collection')
            ->setParameter('collection', $collectionId)
            ->getQuery()
            ->getResult()
        ;
    }
}
