<?php

namespace App\EventSubscriber;

use App\Entity\ItemPurchase;
use App\Entity\ItemQuality;
use App\Entity\ItemSale;
use App\Entity\Purchase;
use App\Entity\Sale;
use App\Event\StateEvent;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StateSubscriber implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function onState(StateEvent $event): void
    {
        /** @var EntityRepository $repo */
        $repo = $this->em->getRepository($event->getClassName());
        $entity = $repo->find($event->getId());
        if ($entity) {
            $state = $event->getState();
            $value = $event->getValue();

            $getMethod = 'is' . ucfirst($state);
            $setMethod = 'set' . ucfirst($state);
            if ($entity instanceof Purchase || $entity instanceof Sale) {
                $entitiesCollection = $this->getIPOrIS($entity);
                foreach ($entitiesCollection as $ipOrIs) {
                    if ($state == 'validate') {
                        if ($ipOrIs instanceof ItemSale) {
                            $this->availableSaleIQ($ipOrIs->getItemQuality(), false);
                        }

                        if (!$entity->isOrder()) {
                            $this->updateItemsNumber($ipOrIs, $entity instanceof Purchase);
                        }
                    } elseif ($state == 'sold') {
                        if (!$entity->isOrder()) {
                            $this->updateItemsNumber($ipOrIs, $entity instanceof Purchase);
                        }
                    } else {
                        $condition = $state == 'send' || $state == 'received' ?
                            $ipOrIs->{$getMethod}() || $ipOrIs->isRefundRequest() : $ipOrIs->{$getMethod}()
                        ;
                        if ($condition) {
                            continue;
                        }

                        $ipOrIs->{$setMethod}($value);
                        if (!$state == 'refundRequest') {
                            $ipOrIs->{$setMethod . 'At'}($ipOrIs->{'get' . ucfirst($state) . 'At'}());
                        }

                        if ($state == 'send' || $state == 'received' || $state == 'refundRequest') {
                            if ($state == 'refundRequest') {
                                $add = $entity instanceof Sale;
                                if ($add) {
                                    $this->availableSaleIQ($ipOrIs->getItemQuality(), true);
                                }
                            } else {
                                $add = $state == 'received';
                            }
                            $this->updateItemsNumber($ipOrIs, $add);
                        }
                    }
                }
            } elseif ($entity instanceof ItemPurchase || $entity instanceof ItemSale) {
                $changeState = true;
                if (
                    $entity instanceof ItemPurchase &&
                    ($state == 'refunded' || $state == 'refundRequest') &&
                    $entity->getQuantityToRefund() &&
                    $entity->getQuantityToRefund() < $entity->getQuantity()
                ) {
                    $changeState = false;
                }

                if ($changeState) {
                    $purchaseOrSale = $entity instanceof ItemPurchase ? $entity->getPurchase() : $entity->getSale();
                    $entityCollections = $this->getIPOrIS($purchaseOrSale);
                    $entitiesFiltered = $entityCollections->filter(
                        function ($entityCollection) use ($state, $getMethod) {
                            if ($state == 'received' || $state == 'send') {
                                return $entityCollection->{$getMethod}() || $entityCollection->isRefundRequest();
                            }
                            return $entityCollection->{$getMethod}();
                        }
                    );

                    if ($entitiesFiltered->count() == $entityCollections->count()) {
                        $purchaseOrSale->{$setMethod}($value);
                    }
                }

                if ($state == 'send' || $state == 'received' || $state == 'refundRequest') {
                    if ($state == 'refundRequest') {
                        $add = $entity instanceof ItemSale;
                        if ($add) {
                            $this->availableSaleIQ($entity->getItemQuality(), true);
                        }
                    } else {
                        $add = $state == 'received';
                    }
                    $this->updateItemsNumber($entity, $state == 'received');
                }
            }
            $this->em->flush();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state' => 'onState',
        ];
    }

    private function getIPOrIS(Purchase|Sale $entity): Collection
    {
        return $entity instanceof Purchase ? $entity->getItemsPurchase() : $entity->getItemSales();
    }

    private function updateItemsNumber(ItemPurchase|ItemSale $entity, bool $add): void
    {
        $item = $entity instanceof ItemPurchase ?
            $entity->getItem() : $entity->getItemQuality()->getItem()
        ;

        if ($entity instanceof ItemSale) {
            $qty = 1;
        } else {
            $qty = $entity->getQuantityToRefund() ?? $entity->getQuantity();
        }

        $itemQty = $add ? $item->getNumber() + $qty : $item->getNumber() - $qty;
        $item->setNumber($itemQty);
    }

    private function availableSaleIQ(ItemQuality $iq, bool $available): void
    {
        $iq->setAvailableSale($available);
    }
}
