<?php

namespace App\EventListener;

use App\Entity\Category;
use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::preRemove, priority: 500, connection: 'default')]
final class DeleteListener
{
    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        $om = $args->getObjectManager();
        if ($entity instanceof Category || $entity instanceof Item) {
            if (!$entity->getChilds()->isEmpty()) {
                foreach ($entity->getChilds() as $child) {
                    $om->remove($child);
                }
            }

            if ($entity instanceof Category) {
                foreach ($entity->getCollections() as $collection) {
                    $collection->setCategory(null);
                }

                foreach ($entity->getItems() as $item) {
                    $item->setCategory(null);
                }
            }
        }
    }
}
