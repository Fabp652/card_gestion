<?php

namespace App\Repository;

use App\Entity\FileManager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FileManager>
 *
 * @method FileManager|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileManager|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileManager[]    findAll()
 * @method FileManager[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileManagerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileManager::class);
    }
}
