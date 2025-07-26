<?php

namespace App\Repository\Trait;

use Doctrine\ORM\QueryBuilder;

trait EntityRepositoryTrait
{
    /**
     * @param QueryBuilder $qb
     * @param string $condition
     * @param string $alias
     * @param mixed $value
     * @return void
     */
    public function addWhere(QueryBuilder $qb, string $condition, string $alias, mixed $value): void
    {
        if (str_contains(strtoupper($condition), 'LIKE')) {
            $value = '%' . $value . '%';
        }
        $qb->andWhere($condition)
            ->setParameter($alias, $value)
        ;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function valueType(mixed $value): mixed
    {
        return is_numeric($value) ? (int) $value : $value;
    }
}
