<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @return array
     */
    public function stats(): array
    {
        return $this->createQueryBuilder('c')
            ->select(
                '
                    SUM(i.price * i.number) AS totalAmount,
                    CASE WHEN COUNT(i.id) > 0 THEN SUM(i.number) ELSE 0 END AS totalItem,
                    c.name AS categoryName,
                    c.id AS categoryId,
                    SUM(i.price * i.number) / SUM(i.number) AS average
                '
            )
            ->leftJoin('c.collections', 'col')
            ->leftJoin('col.items', 'i')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param Category $category
     * @return array
     */
    public function findWithoutActualCategory(Category $category): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.parent = :parent AND c.id != :category')
            ->setParameter('parent', $category->getParent())
            ->setParameter('category', $category)
            ->getQuery()
            ->getResult()
        ;
    }
}
