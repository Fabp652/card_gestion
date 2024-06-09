<?php

namespace App\Repository;

use App\Entity\Card;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use ApiPlatform\Doctrine\Orm\Paginator;
use Doctrine\Common\Collections\Criteria;

/**
 * @extends ServiceEntityRepository<Card>
 *
 * @method Card|null find($id, $lockMode = null, $lockVersion = null)
 * @method Card|null findOneBy(array $criteria, array $orderBy = null)
 * @method Card[]    findAll()
 * @method Card[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CardRepository extends ServiceEntityRepository
{
    private const ITEMS_PER_PAGE = 30;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    public function findPaginated(int $page = 1, int $pageSize = 10, array $orderBy = [], $params = []): array
    {
        $firstResult = ($page - 1) * $pageSize;

        $query = $this->createQueryBuilder('c');

        foreach ($orderBy as $key => $value) {
            $query->orderBy('c.' . $key, $value);
        }

        foreach ($params as $key => $value) {
            $query->andWhere('c.' . $key . '= :' . $key)
                ->setParameter($key, $value)
            ;
        }

        $query->setFirstResult($firstResult)
            ->setMaxResults($pageSize);
        $query->getQuery();

        $paginator = new DoctrinePaginator($query, true);

        $result = [
            'total' => $paginator->count(),
            'actualPage' => $page,
            'list' => $paginator->getIterator(),
            'pageSize' => $pageSize
        ];

        $addPage = $result['total'] % $pageSize > 0 ? 1 : 0;
        $result['maxPage'] = ($result['total'] - $result['total'] % $pageSize) / $pageSize + $addPage;
        return $result;
    }

    /**
     * @param int $rarityId
     * @param int $page
     * @param string $sort
     * @param array $order
     * @return Paginator
     */
    public function findCardsByRarity(
        int $rarityId,
        int $page = 1,
        ?array $order = null
    ): Paginator {
        $firstResult = ($page - 1) * self::ITEMS_PER_PAGE;

        $qb = $this->createQueryBuilder('c')
            ->where('c.rarity = :rarity')
            ->setParameter('rarity', $rarityId)
        ;

        if ($order && !empty($order)) {
            foreach ($order as $orderKey => $orderValue) {
                $qb->orderBy('c.' . $orderKey, $orderValue);
            }
        }

        $criteria = Criteria::create()
            ->setFirstResult($firstResult)
            ->setMaxResults(self::ITEMS_PER_PAGE);
        $qb->addCriteria($criteria);

        $doctrinePaginator = new DoctrinePaginator($qb);
        $paginator = new Paginator($doctrinePaginator);

        return $paginator;
    }
}
