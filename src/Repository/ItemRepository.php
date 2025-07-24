<?php

namespace App\Repository;

use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
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
    public function __construct(ManagerRegistry $registry, private ItemQualityRepository $itemQualityRepository)
    {
        parent::__construct($registry, Item::class);
    }

    /**
     * @param int|null $collectionId
     * @param int|null $storageId
     * @return array
     */
    public function getMinAndMaxPrice(?int $collectionId = null, ?int $storageId = null): array
    {
        $qb = $this->createQueryBuilder('i')
            ->select('MIN(i.price) AS minPrice, MAX(i.price) AS maxPrice')
        ;

        if ($collectionId) {
            $qb->andWhere('i.collection = :collection')
                ->setParameter('collection', $collectionId)
            ;
        }

        if ($storageId) {
            $qb->leftJoin('i.itemQualities', 'iq')
                ->join('iq.storage', 's', Join::WITH, 's.id = :storageId')
                ->setParameter('storageId', $storageId)
            ;
        }

        return $qb->getQuery()
            ->getSingleResult()
        ;
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
     * @param array $filters
     * @param int|null $collectionId
     * @param int|null $categoryId
     * @param int|null $storageId
     * @return QueryBuilder
     */
    public function findByFilter(
        array $filters,
        ?int $collectionId = null,
        ?int $categoryId = null
    ): QueryBuilder {
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
                    ->setParameter($filterKey, '%' . $filterValue . '%')
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
            } elseif ($filterKey == 'number') {
                $comparator = $filterValue == 1 ? '>' : '=';
                $qb->andWhere('i.number ' . $comparator . ' 1');
            } elseif ($filterKey == 'quality') {
                $subQueryQuality = $this->itemQualityRepository->createQueryBuilder('iq')
                    ->select('COUNT(iq.id)')
                    ->where('iq.item = i')
                ;
                switch ($filterValue) {
                    case '2':
                        $qb->andWhere('(' . $subQueryQuality->getDQL() . ') = i.number');
                        break;
                    case '1':
                        $where = '(' . $subQueryQuality->getDQL() . ') < i.number AND (';
                        $where .= str_replace('iq', 'iq2', $subQueryQuality->getDQL()) . ') > 0';
                        $qb->andWhere($where);
                        break;
                    default:
                        $qb->andWhere('(' . $subQueryQuality->getDQL() . ') = 0');
                        break;
                }
            } elseif ($filterKey == 'search') {
                $qb->andWhere('i.name LIKE :search OR i.reference LIKE :search')
                    ->setParameter('search', '%' . $filterValue . '%')
                ;
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

    /**
     * @param int $collectionId
     * @return bool
     */
    public function hasItemWithoutCategory(int $collectionId): bool
    {
        $result = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.collection = :collection')
            ->setParameter('collection', $collectionId)
            ->andWhere('i.category IS NULL')
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result > 0;
    }
}
