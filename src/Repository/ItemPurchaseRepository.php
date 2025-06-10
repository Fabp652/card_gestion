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
     * @return array
     */
    public function getStates(): array
    {
        return self::STATES;
    }
}
