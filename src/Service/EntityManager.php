<?php

namespace App\Service;

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;

class EntityManager
{
    private const EXCEPTION_MESSAGE = 'Une erreur est survenue.';

    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @param object $entity
     * @param bool $flush = false
     * @return array
     */
    public function persist(object $entity, bool $flush = false): array
    {
        try {
            $this->em->persist($entity);
            if ($flush) {
                $this->em->flush();
            }
        } catch (ConnectionException $e) {
            return ['result' => false, 'message' => self::EXCEPTION_MESSAGE];
        }

        return ['result' => true];
    }

    /**
     * @param object $entity
     * @param bool $flush = false
     * @return array
     */
    public function remove(object $entity, bool $flush = false): array
    {
        try {
            $this->em->remove($entity);
            if ($flush) {
                $this->em->flush();
            }
        } catch (ConnectionException $e) {
            return ['result' => false, 'message' => self::EXCEPTION_MESSAGE];
        }

        return ['result' => true];
    }

    /**
     * @return array
     */
    public function flush(): array
    {
        try {
            $this->em->flush();
        } catch (ConnectionException $e) {
            return ['result' => false, 'message' => self::EXCEPTION_MESSAGE];
        }
        return ['result' => true];
    }
}
