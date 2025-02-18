<?php

namespace App\Repository;

use App\Entity\Criteria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Criteria>
 *
 * @method Criteria|null find($id, $lockMode = null, $lockVersion = null)
 * @method Criteria|null findOneBy(array $criteria, array $orderBy = null)
 * @method Criteria[]    findAll()
 * @method Criteria[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CriteriaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Criteria::class);
    }

    /**
     * @param array $filters
     * @return QueryBuilder
     */
    public function findByFilter(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c');

        foreach ($filters as $filterKey => $filterValue) {
            switch ($filterKey) {
                case 'name':
                    $qb->andWhere('c.' . $filterKey . ' LIKE :' . $filterKey)
                        ->setParameter($filterKey, $filterValue . '%')
                    ;
                    break;
                case 'category':
                    $qb->join('c.categories', 'cat')
                        ->join('cat.childs', 'child')
                        ->andWhere('cat.id = :category OR child.id = :category')
                        ->setParameter('category', $filterValue)
                    ;
                    break;
                default:
                    if (is_numeric($filterValue)) {
                        $filterValue = (int) $filterValue;
                    }
                    $qb->andWhere('c.' . $filterKey . ' = ' . ':' . $filterKey)
                        ->setParameter($filterKey, $filterValue)
                    ;
                    break;
            }
        }

        return $qb;
    }
}
