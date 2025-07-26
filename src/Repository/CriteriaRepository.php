<?php

namespace App\Repository;

use App\Entity\Criteria;
use App\Repository\Trait\EntityRepositoryTrait;
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
    use EntityRepositoryTrait;

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
                    $condition = 'c.' . $filterKey . ' LIKE :' . $filterKey;
                    break;
                case 'category':
                    $qb->join('c.categories', 'cat')->join('cat.childs', 'child');
                    $condition = 'cat.id = :' . $filterKey . ' OR child.id = :' . $filterKey;
                    break;
                default:
                    $filterValue = $this->valueType($filterValue);
                    $condition = 'c.' . $filterKey . ' = ' . ':' . $filterKey;
                    break;
            }
            $this->addWhere($qb, $condition, $filterKey, $filterValue);
        }

        return $qb;
    }
}
