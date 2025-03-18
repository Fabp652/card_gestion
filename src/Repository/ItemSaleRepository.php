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
            } elseif ($filterKey == 'price') {
                $filterExplode = explode('-', $filterValue);
                if (count($filterExplode) == 1) {
                    $qb->andWhere('isl.' . $filterKey . ' = :' . $filterKey)
                        ->setParameter($filterKey, $filterValue)
                    ;
                } elseif (empty($filterExplode[0])) {
                    $qb->andWhere('isl.' . $filterKey . ' < :' . $filterKey)
                        ->setParameter($filterKey, $filterExplode[1])
                    ;
                } else {
                    $qb->andWhere('isl. ' . $filterKey . ' BETWEEN :min AND :max')
                        ->setParameter('min', $filterExplode[0])
                        ->setParameter('max', $filterExplode[1])
                    ;
                }
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
