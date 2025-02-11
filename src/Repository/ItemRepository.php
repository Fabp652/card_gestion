<?php

namespace App\Repository;

use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Item>
 *
 * @method Item|null find($id, $lockMode = null, $lockVersion = null)
 * @method Item|null findOneBy(array $criteria, array $orderBy = null)
 * @method Item[]    findAll()
 * @method Item[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    /**
     * @param int $collectionId
     * @return array
     */
    public function getMinAndMaxPrice(int $collectionId): array
    {
        $qb = $this->createQueryBuilder('i')
            ->select('MIN(i.price) AS minPrice, MAX(i.price) AS maxPrice')
            ->where('i.collection = :collection')
            ->setParameter('collection', $collectionId)
            ->getQuery()
            ->getSingleResult()
        ;

        return $qb;
    }
}
