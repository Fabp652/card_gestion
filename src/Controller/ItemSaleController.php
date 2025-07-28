<?php

namespace App\Controller;

use App\Entity\ItemSale;
use App\Entity\Sale;
use App\Event\StateEvent;
use App\Form\FormHiddenType;
use App\Form\ItemSaleType;
use App\Repository\ItemSaleRepository;
use App\Repository\SaleRepository;
use App\Service\EntityManager;
use App\Service\Validate;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/sale')]
class ItemSaleController extends AbstractController
{
    public function __construct(private ItemSaleRepository $itemSaleRepository)
    {
    }

    #[Route('/{saleId}/item', 'app_item_sale_list', ['saleId' => '\d+'])]
    public function list(Request $request, SaleRepository $saleRepo, int $saleId): Response
    {
        /** @var Sale $sale */
        $sale = $saleRepo->find($saleId);

        $query = $request->query;
        if ($query->has('form') && $query->get('form') == 1) {
            $fields = [
                ['name' => 'itemQuality', 'options' => ['required' => true]],
                ['name' => 'price', 'options' => ['required' => true]]
            ];

            /** @var FormInterface $form */
            $form = $this->container->get('form.factory')
                ->createNamed('item_sale', FormHiddenType::class, null, ['fields' => $fields])
            ;

            return $this->render('item_sale/forms.html.twig', [
                'form' => $form->createView(),
                'saleId' => $sale->getId(),
                'itemSales' => $sale->getItemSales()
            ]);
        } else {
            return $this->render('item_sale/index.html.twig', [
                'states' => $this->itemSaleRepository->getStates(),
                'itemSales' => $sale->getItemSales()
            ]);
        }
    }

    #[Route('/{saleId}/item/add', 'app_item_sale_add', ['saleId' => '\d+'])]
    #[Route('/item/{itemSaleId}/edit', 'app_item_sale_edit', ['itemSaleId' => '\d+'])]
    public function addOrEdit(
        Request $request,
        SaleRepository $saleRepo,
        EntityManager $em,
        Validate $validate,
        ?int $saleId,
        ?int $itemSaleId
    ): Response {
        $itemSale = new ItemSale();
        if ($itemSaleId) {
            /** @var ItemSale $itemSale */
            $itemSale = $this->itemSaleRepository->find($itemSaleId);
            if (!$itemSale) {
                return $this->json(['result' => false, 'L\'objet est introuvable.']);
            }
            /** @var Sale $sale */
            $sale = $itemSale->getSale();
        } else {
            /** @var Sale $sale */
            $sale = $saleRepo->find($saleId);
            if (!$saleId) {
                return $this->json(['result' => false, 'L\'achat est introuvable.']);
            }
            $itemSale->setSale($sale);
        }

        $form = $this->createForm(ItemSaleType::class, $itemSale, [
            'itemQualityId' => $request->request->all('item_sale')['itemQuality']
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $new = false;
            if (!$itemSale->getId()) {
                if ($itemSale->getSale()->isSend()) {
                    $itemSale->setSend(true);
                }
                $result = $em->persist($itemSale);
                if (!$result['result']) {
                    return $this->json($result);
                }
                $new = true;
            }
            $result = $em->flush();
            if (!$result['result']) {
                return $this->json($result);
            }

            $sale->caclPrice();
            $em->flush();
            if (!$result['result']) {
                return $this->json($result);
            }

            if ($new) {
                $result['message'] = 'Objet ajouté avec succès.';
                $result['newUrl'] = $this->generateUrl(
                    'app_item_sale_edit',
                    ['itemSaleId' => $itemSale->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $result['deleteUrl'] = $this->generateUrl(
                    'app_item_sale_delete',
                    ['itemSaleId' => $itemSale->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $result['dataUrl'] = $this->generateUrl(
                    'app_item_sale_data',
                    ['itemSaleId' => $itemSale->getId()],
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

    #[Route('/item/{itemSaleId}/delete', 'app_item_sale_delete', ['itemSaleId' => '\d+'])]
    public function delete(EntityManager $em, int $itemSaleId): Response
    {
        /** @var ItemSale $itemSale */
        $itemSale = $this->itemSaleRepository->find($itemSaleId);
        if ($itemSale) {
            $sale = $itemSale->getSale();
            if (!$sale->isValid()) {
                $sale->removeItemSale($itemSale);
                $sale->caclPrice();
                $result = $em->remove($itemSale);
                if ($result['result']) {
                    $result['message'] = 'L\'objet a été retiré avec succès.';
                }
                return $this->json($result);
            }

            return $this->json([
                'result' => false,
                'message' => 'L\'objet ne peut pas être retiré si la vente est validé.'
            ]);
        } else {
            return $this->json(['result' => false, 'message' => 'L\'objet a déjà été retiré.']);
        }
    }

    #[Route('/item/{itemSaleId}/data', 'app_item_sale_data', ['itemSaleId' => '\d+'])]
    public function data(int $itemSaleId): Response
    {
        /** @var ItemSale $itemSale */
        $itemSale = $this->itemSaleRepository->find($itemSaleId);
        if (!$itemSale) {
            return $this->json(['result' => false, 'message' => 'L\'objet est introuvable.']);
        }

        return $this->json(['result' => true, 'data' => [
            'itemQuality' => $itemSale->getItemQuality()->getId(),
            'price' => $itemSale->getPrice()
        ]]);
    }

    #[Route('/item/{itemSaleId}/state', 'app_item_sale_state', ['itemSaleId' => '\d+'])]
    public function state(
        Request $request,
        Validate $validate,
        EntityManager $em,
        EventDispatcherInterface $dispatcher,
        int $itemSaleId
    ): Response {
        /** @var ItemSale $itemSale */
        $itemSale = $this->itemSaleRepository->find($itemSaleId);
        if (!$itemSale) {
            return $this->json(['result' => false, 'message' => 'L\'objet est introuvable.']);
        }

        $data = $request->request->all();
        $state = $data['state'];
        if (!in_array($state, ['send', 'refunded', 'refundRequest'])) {
            return $this->json(['result' => false, 'message' => 'Une erreur est survenue']);
        }

        $method = 'set' . ucfirst($state);
        if (in_array($state, ['send', 'refunded'])) {
            if (empty($data['date'])) {
                return $this->json(['result' => false, 'messages' => ['date' => 'Veuillez choisir une date']]);
            }

            $dateString = str_replace('/', '-', $data['date']);
            $time = strtotime($dateString);
            $itemSale->{$method . 'At'}(new DateTime(date('Y-m-d', $time)));
        }

        if ($state == 'refundRequest' && !empty($data['reason'])) {
            $itemSale->setRefundReason($data['reason']);
        }

        $itemSale->{$method}(true);

        $violations = $validate->validate($itemSale);
        if (!empty($violations)) {
            return $this->json(['result' => false, 'messages' => $violations]);
        }

        $event = new StateEvent($itemSale->getId(), ItemSale::class, $state, true);
        $dispatcher->dispatch($event, 'state');
        return $this->json($em->flush());
    }
}
