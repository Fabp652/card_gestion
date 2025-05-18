<?php

namespace App\Controller;

use App\Repository\ItemPurchaseRepository;
use App\Repository\PurchaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        PaginatorInterface $paginator,
        PurchaseRepository $purchaseRepo,
        int $purchaseId
    ): Response {
        $purchase = $purchaseRepo->find($purchaseId);
        if (!$purchase) {
            return $this->render('error/not_found.html.twig', [
                'message' => 'L\'achat est introuvable.'
            ]);
        }

        $filters = $request->query->all('filter');
        $filters = array_filter(
            $filters,
            function ($filter) {
                return !empty($filter) || $filter == 0;
            }
        );

        $itemPurchases = $this->iPRepo->findByFilter($filters, $purchaseId);
        $itemPurchases = $paginator->paginate(
            $itemPurchases,
            $request->query->get('page', 1),
            $request->query->get('limit', 10)
        );

        $states = $this->iPRepo->getStates();

        return $this->render('item_purchase/index.html.twig', [
            'request' => $request,
            'states' => $states,
            'itemPurchases' => $itemPurchases,
            'purchase' => $purchase
        ]);
    }
}
