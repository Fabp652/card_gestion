<?php

namespace App\Repository;

use App\Entity\ItemSale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * @param array $filters
     * @return QueryBuilder
     */
    public function findByFilter(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('isl');

        foreach ($filters as $filterKey => $filterValue) {
            if ($filterKey == 'name' || $filterKey == 'reference') {
                $qb->andWhere('isl.' . $filterKey . ' LIKE :' . $filterKey)
                    ->setParameter($filterKey, $filterValue . '%')
                ;
            } elseif (str_contains($filterKey, 'min')) {
                $filterKeyExplode = explode('_', $filterKey);
                $qb->andWhere('isl.' . $filterKeyExplode[1] . ' >= :min')
                    ->setParameter('min', $filterValue)
                ;
            } elseif (str_contains($filterKey, 'max')) {
                $filterKeyExplode = explode('_', $filterKey);
                $qb->andWhere('isl.' . $filterKeyExplode[1] . ' <= :max')
                    ->setParameter('max', $filterValue)
                ;
            } else {
                if (is_numeric($filterValue)) {
                    $filterValue = (int) $filterValue;
                }
                $qb->andWhere('isl.' . $filterKey . ' = ' . ':' . $filterKey)
                    ->setParameter($filterKey, $filterValue)
                ;
            }
        }

        return $qb;
    }
}
