<?php

namespace App\Repository;

use App\Entity\StorageType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StorageType>
 *
 * @method StorageType|null find($id, $lockMode = null, $lockVersion = null)
 * @method StorageType|null findOneBy(array $criteria, array $orderBy = null)
 * @method StorageType[]    findAll()
 * @method StorageType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StorageTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StorageType::class);
    }
}
