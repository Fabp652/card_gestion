<?php

namespace App\Controller;

use App\Entity\ItemSale;
use App\Entity\Sale;
use App\Event\StateEvent;
use App\Form\FormHiddenType;
use App\Form\ItemSaleType;
use App\Repository\ItemSaleRepository;
use App\Repository\SaleRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ItemSaleController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private ItemSaleRepository $itemSaleRepository)
    {
    }

    #[Route(
        '/sale/{saleId}/item',
        'app_item_sale_list',
        ['saleId' => '\d+']
    )]
    public function list(
        Request $request,
        SaleRepository $saleRepo,
        int $saleId
    ): Response {
        /** @var Sale $sale */
        $sale = $saleRepo->find($saleId);
        if (!$saleId) {
            return $this->render('error/not_found.html.twig', [
                'message' => 'L\'achat est introuvable.'
            ]);
        }

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

    #[Route(
        '/sale/{saleId}/item/add',
        'app_item_sale_add',
        ['saleId' => '\d+']
    )]
    #[Route(
        '/sale/item/{itemSaleId}/edit',
        name: 'app_item_sale_edit',
        requirements: ['itemSaleId' => '\d+']
    )]
    public function addOrEdit(
        Request $request,
        SaleRepository $saleRepo,
        ?int $saleId,
        ?int $itemSaleId
    ): Response {
        $itemSale = new ItemSale();
        if ($itemSaleId) {
            $itemSale = $this->itemSaleRepository->find($itemSaleId);
            /** @var Sale $sale */
            $sale = $itemSale->getSale();
        } else {
            /** @var Sale $sale */
            $sale = $saleRepo->find($saleId);
            if (!$saleId) {
                return $this->render('error/not_found.html.twig', [
                    'message' => 'L\'achat est introuvable.'
                ]);
            }
            $itemSale->setSale($sale);
        }

        $form = $this->createForm(
            ItemSaleType::class,
            $itemSale,
            ['itemQualityId' => $request->request->all('item_sale')['itemQuality']]
        )->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = ['result' => true];
            $new = false;
            if (!$itemSale->getId()) {
                if ($itemSale->getSale()->isSend()) {
                    $itemSale->setSend(true);
                }
                $this->em->persist($itemSale);
                $new = true;
            }
            $this->em->flush();

            $sale->caclPrice();
            $this->em->flush();

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

    #[Route('/sale/item/{itemSaleId}/delete', name: 'app_item_sale_delete', requirements: ['itemSaleId' => '\d+'])]
    public function delete(int $itemSaleId): Response
    {
        /** @var ItemSale $itemSale */
        $itemSale = $this->itemSaleRepository->find($itemSaleId);
        if ($itemSale) {
            $sale = $itemSale->getSale();
            if (!$sale->isValid()) {
                $sale->removeItemSale($itemSale);
                $sale->caclPrice();
                $this->em->remove($itemSale);
                $this->em->flush();

                return $this->json(['result' => true, 'message' => 'L\'objet a été retiré avec succès.']);
            }

            return $this->json([
                'result' => false,
                'message' => 'L\'objet ne peut pas être retiré si la vente est validé.'
            ]);
        } else {
            return $this->json(['result' => false, 'message' => 'L\'objet a déjà été retiré.']);
        }
    }

    #[Route(
        '/sale/item/{itemSaleId}/data',
        'app_item_sale_data',
        ['itemSaleId' => '\d+']
    )]
    public function data(int $itemSaleId): Response
    {
        /** @var ItemSale $itemSale */
        $itemSale = $this->itemSaleRepository->find($itemSaleId);
        if (!$itemSale) {
            return $this->json(['result' => false, 'message' => 'L\'objet est introuvable.']);
        }

        return $this->json([
            'result' => true,
            'data' => [
                'itemQuality' => $itemSale->getItemQuality()->getId(),
                'price' => $itemSale->getPrice()
            ]
        ]);
    }

    #[Route(
        '/sale/item/{itemSaleId}/state',
        'app_item_sale_state',
        ['itemSaleId' => '\d+']
    )]
    public function state(
        Request $request,
        ValidatorInterface $validator,
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

        $violations = $validator->validate($itemSale);
        if ($violations->count() > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->json(['result' => false, 'messages' => $messages]);
        }

        $event = new StateEvent(
            $itemSale->getId(),
            ItemSale::class,
            $state,
            true
        );

        $dispatcher->dispatch($event, 'state');
        $this->em->flush();

        return $this->json(['result' => true]);
    }
}
