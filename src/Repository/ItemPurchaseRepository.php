<?php

namespace App\Repository;

use App\Entity\ItemPurchase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ItemPurchase>
 */
class ItemPurchaseRepository extends ServiceEntityRepository
{
    private const STATES = [
        'Non reçu',
        'Demande de remboursement',
        'Remboursé',
        'Reçu'
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemPurchase::class);
    }

    /**
     * @param array $filters
     * @param int $purchaseId
     * @return QueryBuilder
     */
    public function findByFilter(array $filters, int $purchaseId): QueryBuilder
    {
        $qb = $this->createQueryBuilder('ip')
            ->where('ip.purchase = :purchaseId')
            ->setParameter('purchaseId', $purchaseId)
            ->leftJoin('ip.item', 'i')
        ;

        foreach ($filters as $filterKey => $filterValue) {
            if ($filterKey == 'name') {
                $qb->andWhere('i.name LIKE :name OR i.reference LIKE :name')
                    ->setParameter($filterKey, $filterValue . '%')
                ;
            } elseif (str_contains($filterKey, 'min')) {
                $filterKeyExplode = explode('_', $filterKey);
                $qb->andWhere('ip.' . $filterKeyExplode[1] . ' >= :min')
                    ->setParameter('min', $filterValue)
                ;
            } elseif (str_contains($filterKey, 'max')) {
                $filterKeyExplode = explode('_', $filterKey);
                $qb->andWhere('ip.' . $filterKeyExplode[1] . ' <= :max')
                    ->setParameter('max', $filterValue)
                ;
            } elseif ($filterKey == 'state') {
                $state = self::STATES[$filterValue];
                switch ($state) {
                    case self::STATES[0]:
                        $qb->andWhere('p.received = 0 AND p.refundRequest = 0');
                        break;
                    case self::STATES[1]:
                        $subQueryIpRefund = $this->createQueryBuilder('p2')
                            ->select('COUNT(ip2.id')
                            ->andWhere('p2.id = p.id')
                            ->join('p2.itemPurchases', 'ip2', Join::WITH, 'ip2.refunded = 1')
                        ;

                        $where = 'p.refundRequest = 1 AND (' . $subQueryIpRefund->getDQL() . ') = 0 AND p.refunded = 0';
                        $qb->andWhere($where);
                        break;
                    case self::STATES[3]:
                        $qb->andWhere('p.refundRequest = 1 AND p.refunded = 1');
                        break;
                    case self::STATES[5]:
                        $qb->andWhere('p.received 1 AND p.refundRequest = 0');
                        break;
                }
            } else {
                if (is_numeric($filterValue)) {
                    $filterValue = (int) $filterValue;
                }
                $qb->andWhere('ip.' . $filterKey . ' = ' . ':' . $filterKey)
                    ->setParameter($filterKey, $filterValue)
                ;
            }
        }
        return $qb;
    }

    /**
     * @return array
     */
    public function getStates(): array
    {
        return self::STATES;
    }
}
