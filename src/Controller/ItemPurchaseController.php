<?php

namespace App\Controller;

use App\Entity\ItemPurchase;
use App\Entity\Purchase;
use App\Form\FormHiddenType;
use App\Form\ItemPurchaseType;
use App\Repository\ItemPurchaseRepository;
use App\Repository\PurchaseRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ItemPurchaseController extends AbstractController
{
    public function __construct(private ItemPurchaseRepository $iPRepo, private EntityManagerInterface $em)
    {
    }

    #[Route(
        '/purchase/{purchaseId}/item',
        name: 'app_item_purchase_list',
        requirements: ['purchaseId' => '\d+']
    )]
    public function list(
        Request $request,
        PurchaseRepository $purchaseRepo,
        int $purchaseId
    ): Response {
        /** @var Purchase $purchase */
        $purchase = $purchaseRepo->find($purchaseId);
        if (!$purchase) {
            return $this->render('error/not_found.html.twig', [
                'message' => 'L\'achat est introuvable.'
            ]);
        }

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
                'request' => $request,
                'states' => $this->iPRepo->getStates(),
                'itemPurchases' => $purchase->getItemsPurchase()
            ]);
        }
    }

    #[Route(
        '/purchase/{purchaseId}/item/add',
        name: 'app_item_purchase_add',
        requirements: ['purchaseId' => '\d+']
    )]
    #[Route(
        '/purchase/item/{itemPurchaseId}/edit',
        name: 'app_item_purchase_edit',
        requirements: ['itemPurchaseId' => '\d+']
    )]
    public function addOrEdit(
        Request $request,
        PurchaseRepository $purchaseRepo,
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
                return $this->render('error/not_found.html.twig', [
                    'message' => 'L\'achat est introuvable.'
                ]);
            }
            $itemPurchase->setPurchase($purchase);
        }

        $form = $this->createForm(
            ItemPurchaseType::class,
            $itemPurchase,
            ['itemId' => $request->request->all('item_purchase')['item']]
        )->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = ['result' => true];
            $new = false;
            if (!$itemPurchase->getId()) {
                if ($itemPurchase->getPurchase()->isReceived()) {
                    $itemPurchase->setReceived(true);
                }
                $this->em->persist($itemPurchase);
                $new = true;
            }
            $this->em->flush();

            $purchase->caclPrice();
            $this->em->flush();

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
            $messages = [];
            foreach ($form->getErrors(true) as $error) {
                $field = $error->getOrigin()->getName();
                $messages[$field] = $error->getMessage();
            }
            return $this->json(['result' => false, 'messages' => $messages]);
        } else {
            return $this->json(['result' => false]);
        }
    }

    #[Route(
        '/purchase/item/{itemPurchaseId}/delete',
        'app_item_purchase_delete',
        ['itemPurchaseId' => '\d+']
    )]
    public function delete(int $itemPurchaseId): Response
    {
        /** @var ItemPurchase $itemPurchase */
        $itemPurchase = $this->iPRepo->find($itemPurchaseId);
        if (!$itemPurchase) {
            return $this->json(['result' => false, 'message' => 'L\'objet a déjà été retiré.']);
        }

        $purchase = $itemPurchase->getPurchase();
        $purchase->removeItemsPurchase($itemPurchase);
        $purchase->caclPrice();

        $this->em->remove($itemPurchase);
        $this->em->flush();

        return $this->json(['result' => true, 'message' => 'L\'objet a été retiré avec succès.']);
    }

    #[Route(
        '/purchase/item/{itemPurchaseId}/data',
        'app_item_purchase_data',
        ['itemPurchaseId' => '\d+']
    )]
    public function data(int $itemPurchaseId): Response
    {
        /** @var ItemPurchase $itemPurchase */
        $itemPurchase = $this->iPRepo->find($itemPurchaseId);
        if (!$itemPurchase) {
            return $this->json(['result' => false, 'message' => 'L\'objet est introuvable.']);
        }

        return $this->json([
            'result' => true,
            'data' => [
                'item' => $itemPurchase->getItem()->getId(),
                'price' => $itemPurchase->getPrice(),
                'quantity' => $itemPurchase->getQuantity(),
                'link' => $itemPurchase->getLink()
            ]
        ]);
    }

    #[Route(
        '/purchase/item/{itemPurchaseId}/state',
        'app_item_purchase_state',
        ['itemPurchaseId' => '\d+']
    )]
    public function state(Request $request, int $itemPurchaseId): Response
    {
        /** @var ItemPurchase $itemPurchase */
        $itemPurchase = $this->iPRepo->find($itemPurchaseId);
        if (!$itemPurchase) {
            return $this->json(['result' => false, 'message' => 'L\'objet est introuvable.']);
        }

        $data = $request->request->all();
        switch ($data['state']) {
            case 'received':
                if (empty($data['date'])) {
                    return $this->json(['result' => false, 'messages' => ['date' => 'Veuillez choisir une date']]);
                }

                $dateString = str_replace('/', '-', $data['date']);
                $time = strtotime($dateString);
                $itemPurchase->setReceived(true)
                    ->setReceivedAt(new DateTime(date('Y-m-d', $time)))
                ;

                $purchase = $itemPurchase->getPurchase();
                $iPReceivedOrRefundRequest = $purchase->getItemsPurchase()->filter(function ($itemPurchase) {
                    return $itemPurchase->isReceived() || $itemPurchase->isRefundRequest();
                });
                if ($iPReceivedOrRefundRequest->count() == $purchase->getItemsPurchase()->count()) {
                    $purchase->setReceived(true);
                }
                break;

            case 'refundRequest':
                $itemPurchase->setRefundRequest(true);
                if (!empty($data['reason'])) {
                    $itemPurchase->setRefundReason($data['reason']);
                }

                $purchase = $itemPurchase->getPurchase();
                $iPRefundRequest = $purchase->getItemsPurchase()->filter(function ($itemPurchase) {
                    return $itemPurchase->isRefundRequest();
                });
                if ($iPRefundRequest->count() == $purchase->getItemsPurchase()->count()) {
                    $purchase->setRefundRequest(true);
                }
                break;

            case 'refunded':
                if (empty($data['date'])) {
                    return $this->json(['result' => false, 'messages' => ['date' => 'Veuillez choisir une date']]);
                }

                $dateString = str_replace('/', '-', $data['date']);
                $time = strtotime($dateString);
                $itemPurchase->setRefunded(true)
                    ->setRefundAt(new DateTime(date('Y-m-d', $time)))
                ;

                $purchase = $itemPurchase->getPurchase();
                $iPRefundRequest = $purchase->getItemsPurchase()->filter(function ($itemPurchase) {
                    return $itemPurchase->isRefunded();
                });
                if ($iPRefundRequest->count() == $purchase->getItemsPurchase()->count()) {
                    $purchase->setRefunded(true);
                }
                break;

            default:
                return $this->json(['result' => false, 'message' => 'Une erreur est survenue']);
                break;
        }
        $this->em->flush();

        return $this->json(['result' => true]);
    }

    private function newForm(
        string $type,
        ?ItemPurchase $itemPurchase = null,
        array $options = [],
        ?Request $request = null
    ): Form {
        $form = $this->createForm($type, $itemPurchase, $options);
        if ($request) {
            $form->handleRequest($request);
        }
        return $form;
    }
}
