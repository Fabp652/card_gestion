<?php

namespace App\Controller;

use App\Entity\ItemQuality;
use App\Repository\ItemQualityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ItemQualityController extends AbstractController
{
    public function __construct(
        private ItemQualityRepository $itemQualityRepository
    ) {
    }

    #[Route('/item/quality/search', name: 'app_item_quality_search')]
    public function search(Request $request): Response
    {
        if ($search = $request->query->get('search')) {
            $storageId = (int) $request->query->get('storageId');
            $notSale = $request->query->get('notSale') == 1;

            $items = $this->itemQualityRepository->search($search, $storageId, $notSale);

            return $this->json(['result' => true, 'items' => $items]);
        }

        return $this->json(['result' => false]);
    }
}
