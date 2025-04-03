<?php

namespace App\Repository;

use App\Entity\Storage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Storage>
 *
 * @method Storage|null find($id, $lockMode = null, $lockVersion = null)
 * @method Storage|null findOneBy(array $criteria, array $orderBy = null)
 * @method Storage[]    findAll()
 * @method Storage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StorageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Storage::class);
    }

    /**
     * @return array
     */
    public function stats(): array
    {
        return $this->createQueryBuilder('s')
            ->select(
                '
                    SUM(i.price) AS totalAmount,
                    CASE WHEN COUNT(iq.id) > 0 THEN COUNT(iq.id) ELSE 0 END AS totalItem,
                    s.name AS storageName,
                    s.id AS storageId,
                    SUM(i.price) / SUM(i.number) AS average,
                    st.name As type,
                    s.capacity,
                    s.full
                '
            )
            ->leftJoin('s.itemQualities', 'iq')
            ->leftJoin('iq.item', 'i')
            ->leftJoin('s.storageType', 'st')
            ->groupBy('s.id')
            ->getQuery()
            ->getResult()
        ;
    }
}
