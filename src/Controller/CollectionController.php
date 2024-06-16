<?php

namespace App\Controller;

use App\Repository\CollectionsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/collection')]
class CollectionController extends AbstractController
{
    public function __construct(private CollectionsRepository $collectionRepo)
    {
    }

    #[Route('/collection', name: 'app_collection')]
    public function index(): Response
    {
        return $this->render('collection/index.html.twig', [
            'controller_name' => 'CollectionController',
        ]);
    }

    #[Route('/list', name: 'app_collection_list')]
    public function list(): Response
    {
        $collections = $this->collectionRepo->findAll();

        return $this->render('collection/partial/_list.html.twig', ['collections' => $collections]);
    }
}
