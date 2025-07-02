<?php

namespace App\Controller;

use App\Entity\Market;
use App\Form\MarketType;
use App\Repository\MarketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MarketController extends AbstractController
{
    public function __construct(private MarketRepository $marketRepo, private EntityManagerInterface $em)
    {
    }

    #[Route('/market', name: 'app_market_list')]
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        $filters = $request->query->all('filter');
        $filters = array_filter(
            $filters,
            function ($filter) {
                return !empty($filter) || $filter == 0;
            }
        );

        $markets = $this->marketRepo->findByFilter($filters);
        $markets = $paginator->paginate(
            $markets,
            $request->query->get('page', 1),
            $request->query->get('limit', 10)
        );

        return $this->render('market/index.html.twig', [
            'request' => $request,
            'markets' => $markets
        ]);
    }

    #[Route('/market/search', 'app_market_search')]
    public function search(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $markets = $this->marketRepo->findByFilter(['search' => $search])
            ->select('m.id', 'm.name')
            ->orderBy('m.name')
            ->getQuery()
            ->getResult()
        ;

        return $this->json(['result' => true, 'searchResults' => $markets]);
    }

    #[Route('/market/add', 'app_market_add')]
    #[Route(
        '/market/{marketId}/edit',
        'app_market_edit',
        ['marketId' => '\d+']
    )]
    public function form(Request $request, ?int $marketId): Response
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
                $this->em->persist($market);
            }
            $this->em->flush();

            return $this->json(['result' => true]);
        } elseif ($form->isSubmitted()) {
            $messages = [];
            foreach ($form->getErrors(true) as $error) {
                $field = $error->getOrigin()->getName();
                $messages[$field] = $error->getMessage();
            }
            return $this->json(['result' => false, 'messages' => $messages]);
        }

        $render = $this->render('market/form.html.twig', [
            'form' => $form->createView(),
            'marketId' => $marketId
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route(
        '/market/{marketId}/delete',
        'app_market_delete',
        ['marketId' => '\d+']
    )]
    public function delete(int $marketId): Response
    {
        $market = $this->marketRepo->find($marketId);
        if (!$market) {
            return $this->json(['result' => false, 'message' => 'La boutique est déjà supprimé.']);
        }

        $this->em->remove($market);
        $this->em->flush();

        return $this->json(['result' => true, 'message' => 'Supprimé avec succès.']);
    }
}
