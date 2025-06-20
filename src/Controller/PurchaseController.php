<?php

namespace App\Controller;

use App\Entity\Purchase;
use App\Form\PurchaseType;
use App\Repository\PurchaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PurchaseController extends AbstractController
{
    public function __construct(private PurchaseRepository $purchaseRepo, private EntityManagerInterface $em)
    {
    }

    #[Route('/purchase', name: 'app_purchase_list')]
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        $filters = $request->query->all('filter');
        $filters = array_filter(
            $filters,
            function ($filter) {
                return !empty($filter) || $filter == 0;
            }
        );

        $purchases = $this->purchaseRepo->findByFilter($filters);

        $purchases = $paginator->paginate(
            $purchases,
            $request->query->get('page', 1),
            $request->query->get('limit', 10)
        );

        return $this->render('purchase/index.html.twig', [
            'request' => $request,
            'purchases' => $purchases,
            'states' => $this->purchaseRepo->getStates()
        ]);
    }

    #[Route('/purchase/add', name: 'app_purchase_add')]
    public function form(Request $request): Response
    {
        $purchase = new Purchase();
        $marketUrl = $this->generateUrl('app_market_search', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $form = $this->createForm(PurchaseType::class, $purchase, ['marketUrl' => $marketUrl])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$purchase->getId()) {
                $this->em->persist($purchase);
            }
            $this->em->flush();

            return $this->json([
                'result' => true,
                'redirect' => $this->generateUrl('app_purchase_edit', ['purchaseId' => $purchase->getId()])
            ]);
        } elseif ($form->isSubmitted()) {
            $messages = [];
            foreach ($form->getErrors(true) as $error) {
                $field = $error->getOrigin()->getName();
                $messages[$field] = $error->getMessage();
            }
            return $this->json(['result' => false, 'messages' => $messages]);
        }

        $render = $this->render('purchase/form.html.twig', [
            'form' => $form->createView()
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route(
        '/purchase/{purchaseId}/delete',
        name: 'app_purchase_delete',
        requirements: ['purchaseId' => '\d+']
    )]
    public function delete(int $purchaseId): Response
    {
        $purchase = $this->purchaseRepo->find($purchaseId);
        if ($purchase) {
            $this->em->remove($purchase);
            $this->em->flush();

            return $this->json(['result' => true]);
        } else {
            return $this->json(['result' => false, 'message' => 'L\'achat est déjà supprimée']);
        }
    }

    #[Route(
        '/purchase/{purchaseId}/edit',
        name: 'app_purchase_edit',
        requirements: ['purchaseId' => '\d+']
    )]
    public function edit(Request $request, int $purchaseId): Response
    {
        $purchase = $this->purchaseRepo->find($purchaseId);
        if (!$purchase) {
            $message = 'L\'achat est introuvable.';
            if ($request->isMethod('GET')) {
                return $this->render('error/not_found.html.twig', [
                    'message' => $message
                ]);
            } else {
                return $this->json(['result' => false, 'message' => $message]);
            }
        }

        $marketUrl = $this->generateUrl('app_market_search', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $form = $this->createForm(PurchaseType::class, $purchase, ['marketUrl' => $marketUrl])->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            return $this->json(['result' => true, 'message' => 'Achat modifié avec succès']);
        } elseif ($form->isSubmitted()) {
            $messages = [];
            foreach ($form->getErrors(true) as $error) {
                $field = $error->getOrigin()->getName();
                $messages[$field] = $error->getMessage();
            }
            return $this->json(['result' => false, 'messages' => $messages]);
        }

        return $this->render('purchase/edit_or_view.html.twig', [
            'purchase' => $purchase,
            'form' => $form
        ]);
    }
}
