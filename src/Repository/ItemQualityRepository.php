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

    /**
     * @param string $search
     */
    public function search(string $search): array
    {
        return $this->createQueryBuilder('iq')
            ->select('iq.id', 'i.name', 'i.reference, c.name AS collectionName, iq.sort')
            ->leftJoin('iq.item', 'i')
            ->leftJoin('i.collection', 'c')
            ->andWhere('i.name LIKE :search OR i.reference LIKE :search')
            ->setParameter('search', $search . '%')
            ->getQuery()
            ->getResult()
        ;
    }
}
