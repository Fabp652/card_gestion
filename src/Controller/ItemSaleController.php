<?php

namespace App\Controller;

use App\Entity\ItemSale;
use App\Form\ItemSaleType;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ItemSaleController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/item/sale', name: 'app_item_sale')]
    public function index(): Response
    {
        return $this->render('item_sale/index.html.twig', [
            'controller_name' => 'ItemSaleController',
        ]);
    }

    #[Route(
        'collection/{collectionId}/item/sale/new',
        name: 'app_item_sale_new',
        requirements: ['collectionId' => '\d+']
    )]
    public function new(Request $request, int $collectionId): Response
    {
        $itemSale = new ItemSale();

        $form = $this->createForm(
            ItemSaleType::class,
            $itemSale,
            ['collection' => $collectionId]
        )->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($itemSale);
            $this->em->flush();

            return $this->json(['result' => true]);
        }

        $render = $this->render('item_sale/form.html.twig', [
            'form' => $form->createView(),
            'collectionId' => $collectionId
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }
}
