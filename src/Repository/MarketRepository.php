<?php

namespace App\Repository;

use App\Entity\Market;
use App\Repository\Trait\EntityRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Market>
 */
class MarketRepository extends ServiceEntityRepository
{
    use EntityRepositoryTrait;

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
                $filterKey = 'name';
                $condition = 'm.' . $filterKey . ' LIKE :' . $filterKey;
            } else {
                $condition = 'm.' . $filterKey . ' = :' . $filterKey;
            }
            $this->addWhere($qb, $condition, $filterKey, $filterValue);
        }
        return $qb;
    }
}
