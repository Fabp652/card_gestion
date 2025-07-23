<?php

namespace App\EventListener;

use App\Entity\Category;
use App\Entity\Item;
use App\Entity\Purchase;
use App\Entity\Rarity;
use App\Entity\Sale;
use App\Entity\Storage;
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
        } elseif ($entity instanceof Purchase) {
            foreach ($entity->getItemsPurchase() as $ip) {
                $om->remove($ip);
            }
        } elseif ($entity instanceof Sale) {
            foreach ($entity->getItemSales() as $is) {
                $om->remove($is);
            }
        } elseif ($entity instanceof Rarity) {
            foreach ($entity->getItems() as $item) {
                $item->setRarity(null);
            }
        } elseif ($entity instanceof Storage) {
            foreach ($entity->getItemQualities() as $iq) {
                $iq->setStorage(null);
            }
        }
    }
}
