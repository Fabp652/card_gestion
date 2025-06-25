<?php

namespace App\Repository;

use App\Entity\Purchase;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Purchase>
 */
class PurchaseRepository extends ServiceEntityRepository
{
    private const STATES = [
        'Brouillon',
        'Non reçu',
        'Demande de remboursement',
        'Partiellement remboursé',
        'Remboursé',
        'Partiellement reçu',
        'Reçu'
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Purchase::class);
    }

    /**
     * @param array $filters
     * @return QueryBuilder
     */
    public function findByFilter(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p');

        foreach ($filters as $filterKey => $filterValue) {
            if ($filterKey == 'name') {
                $qb->andWhere('p.name LIKE :name')
                    ->setParameter($filterKey, $filterValue . '%')
                ;
            } elseif (str_contains($filterKey, 'min')) {
                $filterKeyExplode = explode('_', $filterKey);
                if ($filterKeyExplode[1] == 'buyAt') {
                    $filterValue = DateTime::createFromFormat('d/m/Y', $filterValue);
                }
                $qb->andWhere('p.' . $filterKeyExplode[1] . ' >= :min')
                    ->setParameter('min', $filterValue)
                ;
            } elseif (str_contains($filterKey, 'max')) {
                $filterKeyExplode = explode('_', $filterKey);
                if ($filterKeyExplode[1] == 'buyAt') {
                    $filterValue = DateTime::createFromFormat('d/m/Y', $filterValue);
                }
                $qb->andWhere('p.' . $filterKeyExplode[1] . ' <= :max')
                    ->setParameter('max', $filterValue)
                ;
            } elseif ($filterKey == 'state') {
                $state = self::STATES[$filterValue];
                switch ($state) {
                    case self::STATES[0]:
                        $qb->andWhere('p.isValid = 0');
                        break;
                    case self::STATES[1]:
                        $subQueryIpReceived = $this->createQueryBuilder('p2')
                            ->select('COUNT(ip2.id)')
                            ->andWhere('p2.id = p.id')
                            ->join('p2.itemsPurchase', 'ip2', Join::WITH, 'ip2.received = 1')
                        ;

                        $where = 'p.refundRequest = 0 AND (' . $subQueryIpReceived->getDQL() . ') = 0';
                        $where .= ' AND p.received = 0 AND p.isValid = 1';

                        $qb->andWhere($where);
                        break;
                    case self::STATES[2]:
                        $subQueryIpRefund = $this->createQueryBuilder('p2')
                            ->select('COUNT(ip2.id)')
                            ->andWhere('p2.id = p.id')
                            ->join('p2.itemsPurchase', 'ip2', Join::WITH, 'ip2.refunded = 1')
                        ;

                        $where = 'p.refundRequest = 1 AND (' . $subQueryIpRefund->getDQL() . ') = 0';
                        $where .= ' AND p.isValid = 1';
                        $qb->andWhere($where)
                            ->andWhere('p.refunded = 0 OR p.refunded IS NULL')
                        ;
                        break;
                    case self::STATES[3]:
                        $subQueryIp = $this->createQueryBuilder('p2')
                            ->select('COUNT(ip2.id)')
                            ->andWhere('p2.id = p.id')
                            ->join('p2.itemsPurchase', 'ip2')
                        ;

                        $subQueryIpRefund = $this->createQueryBuilder('p3')
                            ->select('COUNT(ip3.id)')
                            ->andWhere('p3.id = p.id')
                            ->join('p3.itemsPurchase', 'ip3', Join::WITH, 'ip3.refunded = 1')
                        ;

                        $subQueryIpRefund2 = $this->createQueryBuilder('p4')
                            ->select('COUNT(ip4.id)')
                            ->andWhere('p4.id = p.id')
                            ->join('p4.itemsPurchase', 'ip4', Join::WITH, 'ip4.refunded = 1')
                        ;


                        $where = 'p.refundRequest = 1 AND ';
                        $where .= '(' . $subQueryIp->getDQL() . ') ';
                        $where .= '> (' . $subQueryIpRefund->getDQL() . ') AND ';
                        $where .= '(' . $subQueryIpRefund2->getDQL() . ') > 0';

                        $qb->andWhere($where)
                            ->andWhere('p.refunded = 0 OR p.refunded IS NULL')
                        ;
                        break;
                    case self::STATES[4]:
                        $qb->andWhere('p.refundRequest = 1 AND p.refunded = 1');
                        break;
                    case self::STATES[5]:
                        $subQueryIp = $this->createQueryBuilder('p2')
                            ->select('COUNT(ip2.id)')
                            ->andWhere('p2.id = p.id')
                            ->join('p2.itemsPurchase', 'ip2')
                        ;

                        $subQueryIpRefund = $this->createQueryBuilder('p3')
                            ->select('COUNT(ip3.id)')
                            ->andWhere('p3.id = p.id')
                            ->join('p3.itemsPurchase', 'ip3', Join::WITH, 'ip3.received = 1')
                        ;

                        $subQueryIpRefund2 = $this->createQueryBuilder('p4')
                            ->select('COUNT(ip4.id)')
                            ->andWhere('p4.id = p.id')
                            ->join('p4.itemsPurchase', 'ip4', Join::WITH, 'ip4.received = 1')
                        ;

                        $where = 'p.refundRequest = 0 AND p.received = 0 AND ';
                        $where .= '(' . $subQueryIp->getDQL() . ') ';
                        $where .= '> (' . $subQueryIpRefund->getDQL() . ') AND ';
                        $where .= '(' . $subQueryIpRefund2->getDQL() . ') > 0';

                        $qb->andWhere($where);
                        break;
                    case self::STATES[6]:
                        $qb->andWhere('p.received = 1 AND p.refundRequest = 0 AND p.isValid = 1');
                        break;
                }
            } else {
                if (is_numeric($filterValue)) {
                    $filterValue = (int) $filterValue;
                }
                $qb->andWhere('p.' . $filterKey . ' = ' . ':' . $filterKey)
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
