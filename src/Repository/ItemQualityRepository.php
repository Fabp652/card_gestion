<?php

namespace App\Repository;

use App\Entity\ItemQuality;
use App\Repository\Trait\EntityRepositoryTrait;
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
    use EntityRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemQuality::class);
    }

    /**
     * @param array $filters
     * @param int|null $storageId
     * @return QueryBuilder
     */
    public function findByFilter(array $filters, ?int $storageId = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('iq')->leftJoin('iq.item', 'i');
        if ($storageId) {
            $qb->join('iq.storage', 's', Join::WITH, 's.id = :storageId')->setParameter('storageId', $storageId);
        }

        foreach ($filters as $filterKey => $filterValue) {
            if ($filterKey == 'name' || $filterKey == 'reference') {
                $condition = 'i.' . $filterKey . ' LIKE :' . $filterKey;
            } elseif (str_contains($filterKey, 'min')) {
                $filterKeyExplode = explode('_', $filterKey);
                $condition = 'i.' . $filterKeyExplode[1] . ' >= :' . $filterKey;
            } elseif (str_contains($filterKey, 'max')) {
                $filterKeyExplode = explode('_', $filterKey);
                $condition = 'i.' . $filterKeyExplode[1] . ' <= :' . $filterKey;
            } elseif ($filterKey == 'storageId') {
                $condition = 'iq.storage != :' . $filterKey . ' OR iq.storage IS NULL';
            } elseif ($filterKey == 'search') {
                $condition = 'i.name LIKE :' . $filterKey . ' OR i.reference LIKE :' . $filterKey;
            } else {
                $filterValue = $this->valueType($filterValue);
                $condition = 'iq.' . $filterKey . ' = ' . ':' . $filterKey;
            }
            $this->addWhere($qb, $condition, $filterKey, $filterValue);
        }
        return $qb;
    }
}
