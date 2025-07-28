<?php

namespace App\Controller;

use App\Entity\ItemPurchase;
use App\Entity\Purchase;
use App\Event\StateEvent;
use App\Form\FormHiddenType;
use App\Form\ItemPurchaseType;
use App\Repository\ItemPurchaseRepository;
use App\Repository\PurchaseRepository;
use App\Service\EntityManager;
use App\Service\Validate;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

 #[Route('/purchase')]
final class ItemPurchaseController extends AbstractController
{
    public function __construct(private ItemPurchaseRepository $iPRepo)
    {
    }

    #[Route('/{purchaseId}/item', 'app_item_purchase_list', ['purchaseId' => '\d+'])]
    public function list(Request $request, PurchaseRepository $purchaseRepo, int $purchaseId): Response
    {
        /** @var Purchase $purchase */
        $purchase = $purchaseRepo->find($purchaseId);

        $query = $request->query;
        if ($query->has('form') && $query->get('form') == 1) {
            $fields = [
                ['name' => 'item', 'options' => ['required' => true]],
                ['name' => 'price', 'options' => ['required' => true]],
                ['name' => 'quantity', 'options' => ['required' => true]],
                ['name' => 'link', 'options' => ['required' => false]]
            ];

            /** @var FormInterface $form */
            $form = $this->container->get('form.factory')
                ->createNamed('item_purchase', FormHiddenType::class, null, ['fields' => $fields])
            ;

            return $this->render('item_purchase/forms.html.twig', [
                'form' => $form->createView(),
                'purchaseId' => $purchase->getId(),
                'itemsPurchase' => $purchase->getItemsPurchase()
            ]);
        } else {
            return $this->render('item_purchase/index.html.twig', [
                'states' => $this->iPRepo->getStates(),
                'itemPurchases' => $purchase->getItemsPurchase()
            ]);
        }
    }

    #[Route('/{purchaseId}/item/add', 'app_item_purchase_add', ['purchaseId' => '\d+'])]
    #[Route('/item/{itemPurchaseId}/edit', 'app_item_purchase_edit', ['itemPurchaseId' => '\d+'])]
    public function addOrEdit(
        Request $request,
        PurchaseRepository $purchaseRepo,
        EntityManager $em,
        Validate $validate,
        ?int $purchaseId,
        ?int $itemPurchaseId
    ): Response {
        $itemPurchase = new ItemPurchase();
        if ($itemPurchaseId) {
            $itemPurchase = $this->iPRepo->find($itemPurchaseId);
            $purchase = $itemPurchase->getPurchase();
        } else {
            $purchase = $purchaseRepo->find($purchaseId);
            if (!$purchase) {
                return $this->json(['result' => false, 'message' => 'une erreur est survenue.']);
            }
            $itemPurchase->setPurchase($purchase);
        }

        $form = $this->createForm(ItemPurchaseType::class, $itemPurchase, [
            'itemId' => $request->request->all('item_purchase')['item']
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $new = false;
            if (!$itemPurchase->getId()) {
                if ($itemPurchase->getPurchase()->isReceived()) {
                    $itemPurchase->setReceived(true);
                }
                $result = $em->persist($itemPurchase);
                if (!$result['result']) {
                    return $this->json($result);
                }
                $new = true;
            }
            $result = $em->flush();
            if (!$result['result']) {
                return $this->json($result);
            }

            $purchase->caclPrice();
            $result = $em->flush();
            if (!$result['result']) {
                return $this->json($result);
            }

            if ($new) {
                $result['message'] = 'Objet ajouté avec succès.';
                $result['newUrl'] = $this->generateUrl(
                    'app_item_purchase_edit',
                    ['itemPurchaseId' => $itemPurchase->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $result['deleteUrl'] = $this->generateUrl(
                    'app_item_purchase_delete',
                    ['itemPurchaseId' => $itemPurchase->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $result['dataUrl'] = $this->generateUrl(
                    'app_item_purchase_data',
                    ['itemPurchaseId' => $itemPurchase->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            } else {
                $result['message'] = 'Objet modifié avec succès.';
            }
            return $this->json($result);
        } elseif ($form->isSubmitted()) {
            return $this->json(['result' => false, 'messages' => $validate->getFormErrors($form)]);
        } else {
            return $this->json(['result' => false]);
        }
    }

    #[Route('/item/{itemPurchaseId}/delete', 'app_item_purchase_delete', ['itemPurchaseId' => '\d+'])]
    public function delete(EntityManager $em, int $itemPurchaseId): Response
    {
        /** @var ItemPurchase $itemPurchase */
        $itemPurchase = $this->iPRepo->find($itemPurchaseId);
        if (!$itemPurchase) {
            return $this->json(['result' => false, 'message' => 'L\'objet a déjà été retiré.']);
        }

        $purchase = $itemPurchase->getPurchase();
        if (!$purchase->isValid()) {
            $purchase->removeItemsPurchase($itemPurchase);
            $purchase->caclPrice();

            $result = $em->remove($itemPurchase, true);
            if ($result['result']) {
                $this->addFlash('success', 'L\'objet est retiré avec succès.');
            }
            return $this->json($result);
        }

        return $this->json([
            'result' => false,
            'message' => 'L\'objet ne peut pas être retiré si l\'achat est validé.'
        ]);
    }

    #[Route('/item/{itemPurchaseId}/data', 'app_item_purchase_data', ['itemPurchaseId' => '\d+'])]
    public function data(int $itemPurchaseId): Response
    {
        /** @var ItemPurchase $itemPurchase */
        $itemPurchase = $this->iPRepo->find($itemPurchaseId);
        if (!$itemPurchase) {
            return $this->json(['result' => false, 'message' => 'L\'objet est introuvable.']);
        }

        return $this->json(['result' => true, 'data' => [
            'item' => $itemPurchase->getItem()->getId(),
            'price' => $itemPurchase->getPrice(),
            'quantity' => $itemPurchase->getQuantity(),
            'link' => $itemPurchase->getLink()
        ]]);
    }

    #[Route('/item/{itemPurchaseId}/state', 'app_item_purchase_state', ['itemPurchaseId' => '\d+'])]
    public function state(
        Request $request,
        Validate $validate,
        EventDispatcherInterface $dispatcher,
        EntityManager $em,
        int $itemPurchaseId
    ): Response {
        /** @var ItemPurchase $itemPurchase */
        $itemPurchase = $this->iPRepo->find($itemPurchaseId);
        if (!$itemPurchase) {
            return $this->json(['result' => false, 'message' => 'L\'objet est introuvable.']);
        }

        $data = $request->request->all();
        $state = $data['state'];
        if (!in_array($state, ['received', 'refunded', 'refundRequest'])) {
            return $this->json(['result' => false, 'message' => 'Une erreur est survenue']);
        }

        $method = 'set' . ucfirst($state);
        if (in_array($state, ['received', 'refunded'])) {
            if (empty($data['date'])) {
                return $this->json(['result' => false, 'messages' => ['date' => 'Veuillez choisir une date']]);
            }

            $dateString = str_replace('/', '-', $data['date']);
            $time = strtotime($dateString);
            $itemPurchase->{$method . 'At'}(new DateTime(date('Y-m-d', $time)));
        }

        if ($state == 'refundRequest') {
            if (!empty($data['reason'])) {
                $itemPurchase->setRefundReason($data['reason']);
            }

            if (is_numeric($data['quantity']) && $data['quantity'] <= $itemPurchase->getQuantity()) {
                if ($itemPurchase->getQuantityToRefund() && $itemPurchase->getQuantityToRefund() > $data['quantity']) {
                    return $this->json([
                        'result' => false,
                        'messages' => ['quantity' => 'Doit être supérieur à la quantité actuelle']
                    ]);
                }
                $itemPurchase->setQuantityToRefund($data['quantity']);
            }
        }
        $itemPurchase->{$method}(true);

        $violations = $validate->validate($itemPurchase);
        if (!empty($violations)) {
            return $this->json(['result' => true, 'messages' => $violations]);
        }

        $event = new StateEvent($itemPurchase->getId(), ItemPurchase::class, $state, true);
        $dispatcher->dispatch($event, 'state');

        return $this->json($em->flush());
    }
}
