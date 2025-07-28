<?php

namespace App\Controller;

use App\Entity\Market;
use App\Form\MarketType;
use App\Repository\MarketRepository;
use App\Service\EntityManager;
use App\Service\Validate;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('market')]
final class MarketController extends AbstractController
{
    public function __construct(private MarketRepository $marketRepo)
    {
    }

    #[Route(name: 'app_market_list')]
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $request->query;
        $filters = $query->all('filter');
        $filters = array_filter($filters, function ($filter) {
            return !empty($filter) || $filter == 0;
        });

        $markets = $this->marketRepo->findByFilter($filters);
        $markets = $paginator->paginate($markets, $query->get('page', 1), $query->get('limit', 10));

        return $this->render('market/index.html.twig', ['request' => $request, 'markets' => $markets]);
    }

    #[Route('/search', 'app_market_search')]
    public function search(Request $request): Response
    {
        $filters = $request->query->all();
        $markets = $this->marketRepo->findByFilter($filters)
            ->select('m.id', 'm.name AS text')
            ->orderBy('m.name')
            ->getQuery()
            ->getResult()
        ;

        return $this->json(['result' => true, 'searchResults' => $markets]);
    }

    #[Route('/add', 'app_market_add')]
    #[Route('/{marketId}/edit', 'app_market_edit', ['marketId' => '\d+'])]
    public function form(Request $request, EntityManager $em, Validate $validate, ?int $marketId): Response
    {
        $market = new Market();
        if ($marketId) {
            /** @var Market $market */
            $market = $this->marketRepo->find($marketId);
            if (!$market) {
                return $this->json(['result' => false, 'message' => 'Une erreur est survenue.']);
            }
        }

        $form = $this->createForm(MarketType::class, $market)->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$market->getId()) {
                $addOrUpdateMessage = 'ajoutée';
                $result = $em->persist($market, true);
            } else {
                $addOrUpdateMessage = 'modifiée';
                $result = $em->flush();
            }

            $this->addFlash('success', 'Boutique ' . $addOrUpdateMessage . ' avec succès.');
            return $this->json($result);
        } elseif ($form->isSubmitted()) {
            return $this->json(['result' => false, 'messages' => $validate->getFormErrors($form)]);
        }

        $render = $this->render('market/form.html.twig', ['form' => $form->createView(), 'marketId' => $marketId]);
        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route('/{marketId}/delete', 'app_market_delete', ['marketId' => '\d+'])]
    public function delete(EntityManager $em, int $marketId): Response
    {
        $market = $this->marketRepo->find($marketId);
        if (!$market) {
            return $this->json(['result' => false, 'message' => 'La boutique est déjà supprimé.']);
        }

        $result = $em->remove($market, true);
        if ($result['result']) {
            $this->addFlash('success', 'Boutique supprimé avec succès.');
        }
        return $this->json($result);
    }
}
