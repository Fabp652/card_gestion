<?php

namespace App\Repository;

use App\Entity\Rarity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rarity>
 *
 * @method Rarity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rarity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rarity[]    findAll()
 * @method Rarity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RarityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rarity::class);
    }

    /**
     * @param int $collectionId
     * @return array
     */
    public function stats(int $collectionId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.collection = :collection')
            ->setParameter('collection', $collectionId)
            ->leftJoin('r.items', 'i')
            ->leftJoin('r.file', 'f')
            ->select(
                '
                    SUM(i.price * i.number) AS totalAmount,
                    SUM(i.number) AS totalItem,
                    r.name AS rarityName,
                    r.grade,
                    r.id,
                    f.id AS fileId
                '
            )
            ->groupBy('r.id')
            ->orderBy('r.grade')
            ->getQuery()
            ->getResult()
        ;
    }
}
