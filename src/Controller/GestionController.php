<?php

namespace App\Controller;

use App\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GestionController extends AbstractController
{
    /**
     * @var CardRepository
     */
    private CardRepository $cardRepo;

    /**
     * @param CardRepository $cardRepo
     */
    public function __construct(CardRepository $cardRepo)
    {
        $this->cardRepo = $cardRepo;
    }

    #[Route('/', name: 'app_gestion')]
    public function index(): Response
    {
        $cards = $this->cardRepo->findAll();

        return $this->render('gestion/index.html.twig', [
            'cards' => $cards
        ]);
    }
}
