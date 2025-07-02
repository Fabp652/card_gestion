<?php

namespace App\Repository;

use App\Entity\Market;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Market>
 */
class MarketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Market::class);
    }

    /**
     * @param array $filters
     * @return QueryBuilder
     */
    public function findByFilter(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('m');

        foreach ($filters as $filterKey => $filterValue) {
            if ($filterKey == 'name' || $filterKey == 'search') {
                $qb->andWhere('m.name LIKE :name')
                    ->setParameter('name', '%' . $filterValue . '%')
                ;
            } else {
                $qb->andWhere('m.' . $filterKey . ' = :' . $filterKey)
                    ->setParameter($filterKey, $filterValue)
                ;
            }
        }

        return $qb;
    }
}
