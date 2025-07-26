<?php

namespace App\Repository;

use App\Entity\ItemSale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ItemSale>
 *
 * @method ItemSale|null find($id, $lockMode = null, $lockVersion = null)
 * @method ItemSale|null findOneBy(array $criteria, array $orderBy = null)
 * @method ItemSale[]    findAll()
 * @method ItemSale[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemSaleRepository extends ServiceEntityRepository
{
    private const STATES = [
        'En vente',
        'Non envoyé',
        'Demande de remboursement',
        'Remboursé',
        'Envoyé',
        'Vendu'
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemSale::class);
    }

    /**
     * @return array
     */
    public function getStates(): array
    {
        return self::STATES;
    }
}
