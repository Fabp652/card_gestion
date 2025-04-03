<?php

namespace App\Repository;

use App\Entity\ItemQuality;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
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
     * @param int $storageId
     * @param bool $notSale
     */
    public function search(string $search, int $storageId, bool $notSale): array
    {
        $concat = "CASE WHEN i.reference IS NOT NULL THEN CONCAT('N°', iq.sort, ' ', i.reference, ' - ', i.name, ";
        $concat .= "' (', c.name, ')') ELSE CONCAT('N°', iq.sort, ' ', i.name, ' (', c.name, ')') END AS text";

        $qb = $this->createQueryBuilder('iq')
            ->leftJoin('iq.item', 'i')
            ->leftJoin('i.collection', 'c')
            ->andWhere('i.name LIKE :search OR i.reference LIKE :search')
            ->setParameter('search', $search . '%')
            ->select('iq.id', $concat)
            ->setMaxResults(30)
        ;

        if ($storageId) {
            $qb->andWhere('iq.storage != :storage OR iq.storage IS NULL')
                ->setParameter('storage', $storageId)
            ;
        }

        if ($notSale) {
            $qb->andWhere('iq.itemSale IS NULL');
        }

        return $qb->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param array $filters
     * @param int|null $storageId
     * @return QueryBuilder
     */
    public function findByFilter(array $filters, ?int $storageId = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('iq')
            ->leftJoin('iq.item', 'i')
        ;

        if ($storageId) {
            $qb->join('iq.storage', 's', Join::WITH, 's.id = :storageId')
                ->setParameter('storageId', $storageId)
            ;
        }

        foreach ($filters as $filterKey => $filterValue) {
            if (is_numeric($filterValue)) {
                $filterValue = (int) $filterValue;
            }
            if ($filterKey == 'name' || $filterKey == 'reference') {
                $qb->andWhere('i.' . $filterKey . ' LIKE :' . $filterKey)
                    ->setParameter($filterKey, $filterValue . '%')
                ;
            } elseif (str_contains($filterKey, 'min')) {
                $filterKeyExplode = explode('_', $filterKey);
                $qb->andWhere('i.' . $filterKeyExplode[1] . ' >= :min')
                    ->setParameter('min', $filterValue)
                ;
            } elseif (str_contains($filterKey, 'max')) {
                $filterKeyExplode = explode('_', $filterKey);
                $qb->andWhere('i.' . $filterKeyExplode[1] . ' <= :max')
                    ->setParameter('max', $filterValue)
                ;
            } else {
                $qb->andWhere('iq.' . $filterKey . ' = ' . ':' . $filterKey)
                    ->setParameter($filterKey, $filterValue)
                ;
            }
        }

        return $qb;
    }
}
