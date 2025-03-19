<?php

namespace App\Controller;

use App\Entity\ItemSale;
use App\Form\ItemSaleType;
use App\Repository\ItemQualityRepository;
use App\Repository\ItemRepository;
use App\Repository\ItemSaleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ItemSaleController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private ItemSaleRepository $itemSaleRepository)
    {
    }

    #[Route('/item/sale', name: 'app_item_sale_list')]
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        $filters = $request->query->all('filter');
        $filters = array_filter(
            $filters,
            function ($filter) {
                return !empty($filter) || $filter == 0;
            }
        );

        $itemSales = $this->itemSaleRepository->findByFilter($filters);

        $itemSales = $paginator->paginate(
            $itemSales,
            $request->query->get('page', 1),
            $request->query->get('limit', 10)
        );

        return $this->render('item_sale/index.html.twig', [
            'request' => $request,
            'itemSales' => $itemSales
        ]);
    }

    #[Route('/item/sale/new', name: 'app_item_sale_new')]
    #[Route(
        '/item/sale/{itemSaleId}/edit',
        name: 'app_item_sale_edit',
        requirements: ['itemSaleId' => '\d+']
    )]
    public function form(Request $request, ?int $itemSaleId): Response
    {
        if ($itemSaleId) {
            $itemSale = $this->itemSaleRepository->find($itemSaleId);
        } else {
            $itemSale = new ItemSale();
        }

        $form = $this->createForm(ItemSaleType::class, $itemSale)->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($itemSale);
            $this->em->flush();

            return $this->json(['result' => true]);
        }

        $render = $this->render('item_sale/form.html.twig', [
            'form' => $form->createView()
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route('/item/sale/{itemSaleId}/delete', name: 'app_item_sale_delete', requirements: ['itemSaleId' => '\d+'])]
    public function delete(Request $request, int $itemSaleId): Response
    {
        $referer = $request->headers->get('referer');

        $itemSale = $this->itemSaleRepository->find($itemSaleId);
        if ($itemSale) {
            $itemSale->removeAllItemQualities();
            $this->em->remove($itemSale);
            $this->em->flush();
            $this->addFlash('success', "La vente est supprimé");
        } else {
            $this->addFlash('warning', "La vente est déjà supprimer");
        }

        return $this->redirect($referer);
    }

    #[Route(
        '/item/sale/{itemSaleId}/view',
        name: 'app_item_sale_view',
        requirements: ['itemSaleId' => '\d+']
    )]
    public function view(
        Request $request,
        PaginatorInterface $paginator,
        ItemQualityRepository $itemQualityRepository,
        int $itemSaleId
    ): Response {
        $itemSale = $this->itemSaleRepository->find($itemSaleId);

        $filters = $request->query->all('filter');
        $filters = array_filter(
            $filters,
            function ($filter) {
                return !empty($filter) || $filter == 0;
            }
        );

        $itemQualities = $itemQualityRepository->findByFilter($filters)
            ->andWhere('iq.itemSale = :itemSale')
            ->setParameter('itemSale', $itemSaleId)
        ;

        $itemQualities = $paginator->paginate(
            $itemQualities,
            $request->query->get('page', 1),
            $request->query->get('limit', 10)
        );

        $itemQualitiesOption = $itemQualityRepository->findBy(['itemSale' => null]);

        return $this->render('item_sale/view.html.twig', [
            'itemSale' => $itemSale,
            'itemQualities' => $itemQualities,
            'itemQualitiesOption' => $itemQualitiesOption
        ]);
    }

    #[Route(
        '/item/sale/{itemSaleId}/update',
        name: 'app_item_sale_update',
        requirements: ['itemSaleId' => '\d+']
    )]
    public function update(
        Request $request,
        ItemQualityRepository $itemQualityRepository,
        int $itemSaleId
    ): Response {
        $itemSale = $this->itemSaleRepository->find($itemSaleId);

        $datas = $request->request->all();
        foreach ($datas as $dataKey => $dataValue) {
            if ($dataKey === 'itemQuality' && $dataValue) {
                $itemQuality = $itemQualityRepository->find($dataValue);
                $itemSale->addItemQuality($itemQuality);
            } elseif ($dataKey === 'price' && $dataValue) {
                $itemSale->setPrice((float) $dataValue);
            } elseif ($dataKey === 'sold') {
                $sold = $dataValue == 'true';
                $itemSale->setSold($sold);
            }
        }
        $this->em->flush();

        return $this->json(['result' => true]);
    }

    #[Route(
        '/item/sale/{itemSaleId}/quality/{itemQualityId}/remove',
        name: 'app_item_sale_remove',
        requirements: ['itemSaleId' => '\d+', 'itemQualityId' => '\d+']
    )]
    public function remove(
        Request $request,
        ItemQualityRepository $itemQualityRepository,
        int $itemSaleId,
        int $itemQualityId
    ): Response {
        $referer = $request->headers->get('referer');

        $itemSale = $this->itemSaleRepository->find($itemSaleId);
        $itemQuality = $itemQualityRepository->find($itemQualityId);
        if ($itemSale) {
            $itemSale->removeItemQuality($itemQuality);
            $this->em->flush();
            $this->addFlash('success', "L'objet est retiré");
        } else {
            $this->addFlash('warning', "L'objet est déjà retiré");
        }

        return $this->redirect($referer);
    }
}
