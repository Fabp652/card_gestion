<?php

namespace App\Controller;

use App\Entity\ItemSale;
use App\Form\ItemSaleType;
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
}
