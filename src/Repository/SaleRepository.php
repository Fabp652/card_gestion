<?php

namespace App\Repository;

use App\Entity\Sale;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sale>
 */
class SaleRepository extends ServiceEntityRepository
{
    private const STATES = [
        'Brouillon',
        'En vente',
        'Non envoyé',
        'Demande de remboursement',
        'Partiellement remboursé',
        'Remboursé',
        'Partiellement envoyé',
        'Envoyé',
        'Vendu'
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sale::class);
    }

    /**
     * @param array $filters
     * @return QueryBuilder
     */
    public function findByFilter(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('s');

        foreach ($filters as $filterKey => $filterValue) {
            if ($filterKey == 'name') {
                $qb->andWhere('s.name LIKE :name')
                    ->setParameter($filterKey, $filterValue . '%')
                ;
            } elseif (str_contains($filterKey, 'min')) {
                $filterKeyExplode = explode('_', $filterKey);
                if ($filterKeyExplode[1] == 'buyAt') {
                    $filterValue = DateTime::createFromFormat('d/m/Y', $filterValue);
                }
                $qb->andWhere('s.' . $filterKeyExplode[1] . ' >= :min')
                    ->setParameter('min', $filterValue)
                ;
            } elseif (str_contains($filterKey, 'max')) {
                $filterKeyExplode = explode('_', $filterKey);
                if ($filterKeyExplode[1] == 'buyAt') {
                    $filterValue = DateTime::createFromFormat('d/m/Y', $filterValue);
                }
                $qb->andWhere('s.' . $filterKeyExplode[1] . ' <= :max')
                    ->setParameter('max', $filterValue)
                ;
            } elseif ($filterKey == 'state') {
                $state = self::STATES[$filterValue];
                switch ($state) {
                    case self::STATES[0]:
                        $qb->andWhere('s.isValid = 0');
                        break;
                    case self::STATES[1]:
                        $qb->andWhere('s.isValid = 1 AND s.sold = 0');
                        break;
                    case self::STATES[2]:
                        $subQueryIsSend = $this->createQueryBuilder('s2')
                            ->select('COUNT(itemSale2.id)')
                            ->andWhere('s2.id = s.id')
                            ->join('s2.itemSales', 'itemSale2', Join::WITH, 'itemSale.send = 1')
                        ;

                        $where = 's.isValid = 1 AND s.sold = 1 AND s.isOrder = 1';
                        $where .= '(' . $subQueryIsSend->getDQL() . ')';
                        $qb->andWhere($where)
                            ->andWhere('s.send = 0 OR s.send IS NULL')
                        ;
                        break;
                    case self::STATES[3]:
                        $subQueryIsRefund = $this->createQueryBuilder('s2')
                            ->select('COUNT(itemSale2.id)')
                            ->andWhere('s2.id = s.id')
                            ->join('s2.itemSales', 'itemSale2', Join::WITH, 'itemSale2.refunded = 1')
                        ;

                        $where = 's.refundRequest = 1 AND (' . $subQueryIsRefund->getDQL() . ') = 0';
                        $where .= ' AND s.isValid = 1';
                        $qb->andWhere($where)
                            ->andWhere('s.refunded = 0 OR s.refunded IS NULL')
                        ;
                        break;
                    case self::STATES[4]:
                        $subQueryIs = $this->createQueryBuilder('s2')
                            ->select('COUNT(itemSale2.id)')
                            ->andWhere('s2.id = s.id')
                            ->join('s2.itemSales', 'itemSale2')
                        ;

                        $subQueryIsRefund = $this->createQueryBuilder('s3')
                            ->select('COUNT(itemSale3.id)')
                            ->andWhere('s3.id = s.id')
                            ->join('s3.itemSales', 'itemSale3', Join::WITH, 'itemSale3.refunded = 1')
                        ;

                        $subQueryIsRefund2 = $this->createQueryBuilder('s4')
                            ->select('COUNT(itemSale4.id)')
                            ->andWhere('s4.id = s.id')
                            ->join('s4.itemSales', 'itemSale4', Join::WITH, 'itemSale4.refunded = 1')
                        ;


                        $where = 's.refundRequest = 1 AND ';
                        $where .= '(' . $subQueryIs->getDQL() . ') ';
                        $where .= '> (' . $subQueryIsRefund->getDQL() . ') AND ';
                        $where .= '(' . $subQueryIsRefund2->getDQL() . ') > 0';

                        $qb->andWhere($where)
                            ->andWhere('s.refunded = 0 OR s.refunded IS NULL')
                        ;
                        break;
                    case self::STATES[5]:
                        $qb->andWhere('s.refundRequest = 1 AND s.refunded = 1');
                        break;
                    case self::STATES[6]:
                        $subQueryIp = $this->createQueryBuilder('s2')
                            ->select('COUNT(itemSale2.id)')
                            ->andWhere('s2.id = s.id')
                            ->join('s2.itemSales', 'itemSale2')
                        ;

                        $subQueryIpRefund = $this->createQueryBuilder('s3')
                            ->select('COUNT(itemSale3.id)')
                            ->andWhere('s3.id = s.id')
                            ->join('s3.itemSales', 'itemSale3', Join::WITH, 'itemSale3.send = 1')
                        ;

                        $subQueryIpRefund2 = $this->createQueryBuilder('s4')
                            ->select('COUNT(itemSale4.id)')
                            ->andWhere('s4.id = s.id')
                            ->join('s4.itemSales', 'itemSale4', Join::WITH, 'itemSale4.send = 1')
                        ;

                        $where = 's.refundRequest = 0 AND ';
                        $where .= '(' . $subQueryIp->getDQL() . ') ';
                        $where .= '> (' . $subQueryIpRefund->getDQL() . ') AND ';
                        $where .= '(' . $subQueryIpRefund2->getDQL() . ') > 0';

                        $qb->andWhere($where)
                            ->andWhere('s.send IS NULL OR s.send = 0')
                        ;
                        break;
                    case self::STATES[7]:
                        $qb->andWhere('s.send = 1 AND s.refundRequest = 0 AND s.isValid = 1 AND s.sold = 1');
                        break;
                    case self::STATES[8]:
                        $qb->andWhere('s.refundRequest = 0 AND s.isValid = 1 AND s.sold = 1');
                        break;
                }
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
