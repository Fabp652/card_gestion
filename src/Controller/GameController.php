<?php

namespace App\Controller;

use App\Entity\Game;
use App\Form\GameType;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class GameController extends AbstractController
{
    public function __construct(private GameRepository $gameRepository, private EntityManagerInterface $em) {}

    #[Route('/game', name: 'app_game')]
    public function index(): Response
    {
        $games = $this->gameRepository->findBy([], ['price' => 'DESC']);

        $total = count($games);
        $totalAmount = 0;
        foreach ($games as $game) {
            $price = $game->getPrice() * $game->getNumber();
            $totalAmount += $price;
        }

        return $this->render('game/index.html.twig', [
            'games' => $games,
            'total' => $total,
            'totalAmount' => $totalAmount
        ]);
    }

    #[Route('/game/new', name: 'app_game_new')]
    #[Route('/game/update/{id}', name: 'app_game_update')]
    public function new(Request $request, int $id = null): Response
    {
        $game = new Game();
        if ($id) {
            $game = $this->em->find(Game::class, $id);
        }

        $form = $this->createForm(GameType::class, $game);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $game = $form->getData();
            if (!$game->getId()) {
                $this->em->persist($game);
            }
            $this->em->flush();

            return $this->redirectToRoute('app_game');
        }

        return $this->render('game/form.html.twig', [
            'form' => $form,
            'id' => $game->getId()
        ]);
    }
    
    #[Route('/game/delete/{id}', name: 'delete_game')]
    public function delete(int $id): Response
    {
        $game = $this->em->find(Game::class, $id);

        if ($game) {
            $this->em->remove($game);
            $this->em->flush();
        }

        return $this->redirectToRoute('app_game');
    }
}
