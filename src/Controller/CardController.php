<?php

namespace App\Controller;

use App\Entity\Card;
use App\Form\CardType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CardController extends AbstractController
{
    /** @var EntityManagerInterface $em */
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/card/new', name: 'app_card')]
    #[Route('/card/update/{id}', name: 'app_card_update')]
    public function new(Request $request, int $id = null): Response
    {
        $card = new Card();
        if ($id) {
            $card = $this->em->find(Card::class, $id);
        }

        $form = $this->createForm(CardType::class, $card);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $card = $form->getData();
            if (!$card->getId()) {
                $this->em->persist($card);
            }
            $this->em->flush();

            return $this->redirectToRoute('app_gestion');
        }

        return $this->render('card/index.html.twig', [
            'form' => $form,
            'id' => $card->getId()
        ]);
    }
    
    #[Route('/card/delete/{id}', name: 'delete_card')]
    public function delete(int $id): Response
    {
        $card = $this->em->find(Card::class, $id);

        if ($card) {
            $this->em->remove($card);
            $this->em->flush();
        }

        return $this->redirectToRoute('app_gestion');
    }
}
