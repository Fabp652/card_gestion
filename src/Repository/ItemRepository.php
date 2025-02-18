<?php

namespace App\Repository;

use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Item>
 *
 * @method Item|null find($id, $lockMode = null, $lockVersion = null)
 * @method Item|null findOneBy(array $criteria, array $orderBy = null)
 * @method Item[]    findAll()
 * @method Item[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    /**
     * @param int $collectionId
     * @return array
     */
    public function getMinAndMaxPrice(int $collectionId): array
    {
        $qb = $this->createQueryBuilder('i')
            ->select('MIN(i.price) AS minPrice, MAX(i.price) AS maxPrice')
            ->where('i.collection = :collection')
            ->setParameter('collection', $collectionId)
            ->getQuery()
            ->getSingleResult()
        ;

        return $qb;
    }

    /**
     * @param ?int $collectionId
     * @param ?int $categoryId
     * @return array
     */
    public function findMostExpensives(?int $collectionId, ?int $categoryId): array
    {
        $qb = $this->createQueryBuilder('ime')
            ->select('ime.price, ime.number, ime.name, rme.name AS rarityName')
            ->leftJoin('ime.rarity', 'rme')
            ->orderBy('ime.price', 'DESC')
            ->setMaxResults(10)
        ;

        if ($collectionId) {
            $qb->andWhere('ime.collection = :collection')
                ->setParameter('collection', $collectionId)
            ;
        }

        if ($categoryId) {
            $qb->andWhere('ime.category = :category')
                ->setParameter('category', $categoryId)
            ;
        }

        return $qb->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param int $collectionId
     * @return array
     */
    public function statByRarity(int $collectionId): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.collection = :collection')
            ->setParameter('collection', $collectionId)
            ->select(
                '
                    SUM(i.price * i.number) AS totalAmount,
                    SUM(i.number) AS totalItem,
                    r.name AS rarityName,
                    SUM(i.price * i.number) / SUM(i.number) AS average
                '
            )
            ->join('i.rarity', 'r')
            ->groupBy('i.rarity')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param array $filters
     * @param int|null $collectionId
     * @param int|null $categoryId
     * @return QueryBuilder
     */
    public function findByFilter(array $filters, ?int $collectionId, ?int $categoryId): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.rarity', 'r')
        ;

        if ($collectionId) {
            $qb->andWhere('i.collection = :collectionId')
                ->setParameter('collectionId', $collectionId)
            ;
        }

        if ($categoryId) {
            $qb->andWhere('i.category = :categoryId')
                ->setParameter('categoryId', $categoryId)
            ;
        }

        foreach ($filters as $filterKey => $filterValue) {
            if ($filterKey == 'name' || $filterKey == 'reference') {
                $qb->andWhere('i.' . $filterKey . ' LIKE :' . $filterKey)
                    ->setParameter($filterKey, $filterValue . '%')
                ;
            } elseif ($filterKey == 'price' || $filterKey == 'quality') {
                $filterExplode = explode('-', $filterValue);
                if (count($filterExplode) == 1) {
                    $qb->andWhere('i.' . $filterKey . ' = :' . $filterKey)
                        ->setParameter($filterKey, $filterValue)
                    ;
                } elseif (empty($filterExplode[0])) {
                    $qb->andWhere('i.' . $filterKey . ' < :' . $filterKey)
                        ->setParameter($filterKey, $filterExplode[1])
                    ;
                } else {
                    $qb->andWhere('i. ' . $filterKey . ' BETWEEN :min AND :max')
                        ->setParameter('min', $filterExplode[0])
                        ->setParameter('max', $filterExplode[1])
                    ;
                }
            } elseif ($filterKey == 'number') {
                $comparator = $filterValue == 1 ? '>' : '=';
                $qb->andWhere('i.number ' . $comparator . ' 1');
            } else {
                if (is_numeric($filterValue)) {
                    $filterValue = (int) $filterValue;
                }
                $qb->andWhere('i.' . $filterKey . ' = ' . ':' . $filterKey)
                    ->setParameter($filterKey, $filterValue)
                ;
            }
        }
        return $qb;
    }
}
