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
    #[Route(
        '/purchase/{purchaseId}/edit',
        name: 'app_purchase_edit',
        requirements: ['purchaseId' => '\d+']
    )]
    public function form(Request $request, ?int $purchaseId): Response
    {
        $purchase = new Purchase();
        if ($purchaseId) {
            $purchase = $this->purchaseRepo->find($purchaseId);
        }

        $form = $this->createForm(PurchaseType::class, $purchase)->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$purchase->getId()) {
                $this->em->persist($purchase);
            }
            $this->em->flush();

            return $this->json([
                'result' => true,
                'redirect' => $this->generateUrl('app_item_purchase_list', ['purchaseId' => $purchase->getId()])
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
            'form' => $form->createView(),
            'purchaseId' => $purchaseId
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
        dd($purchase);
        if ($purchase) {
            $this->em->remove($purchase);
            $this->em->flush();

            return $this->json(['result' => true]);
        } else {
            return $this->json(['result' => false, 'message' => 'L\'a déjà est déjà supprimée']);
        }
    }
}
