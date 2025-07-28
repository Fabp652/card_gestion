<?php

namespace App\Controller;

use App\Entity\Purchase;
use App\Event\StateEvent;
use App\Form\PurchaseType;
use App\Repository\PurchaseRepository;
use App\Service\EntityManager;
use App\Service\Validate;
use DateTime;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/purchase')]
final class PurchaseController extends AbstractController
{
    public function __construct(private PurchaseRepository $purchaseRepo)
    {
    }

    #[Route(name: 'app_purchase_list')]
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $request->query;
        $filters = $query->all('filter');
        $filters = array_filter($filters, function ($filter) {
            return !empty($filter) || $filter == 0;
        });

        $purchases = $this->purchaseRepo->findByFilter($filters);
        $purchases = $paginator->paginate($purchases, $query->get('page', 1), $query->get('limit', 10));

        return $this->render('purchase/index.html.twig', [
            'request' => $request,
            'purchases' => $purchases,
            'states' => $this->purchaseRepo->getStates()
        ]);
    }

    #[Route('/add', 'app_purchase_add')]
    public function form(Request $request, EntityManager $em, Validate $validate): Response
    {
        $purchase = new Purchase();
        $marketUrl = $this->generateUrl('app_market_search', ['forBuy' => 1], UrlGeneratorInterface::ABSOLUTE_URL);
        $form = $this->createForm(PurchaseType::class, $purchase, [
            'marketUrl' => $marketUrl,
            'post' => $request->isMethod('POST')
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$purchase->isOrder()) {
                $purchase->setReceived(true);
            }

            $result = $em->persist($purchase, true);
            if ($result['result']) {
                $result['redirect'] = $this->generateUrl('app_purchase_edit', ['purchaseId' => $purchase->getId()]);
            }
            return $this->json($result);
        } elseif ($form->isSubmitted()) {
            return $this->json(['result' => false, 'messages' => $validate->getFormErrors($form)]);
        }

        $render = $this->render('purchase/form.html.twig', [
            'form' => $form->createView()
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route('/{purchaseId}/delete', 'app_purchase_delete', ['purchaseId' => '\d+'])]
    public function delete(Request $request, EntityManager $em, int $purchaseId): Response
    {
        $purchase = $this->purchaseRepo->find($purchaseId);
        if ($purchase) {
            if (!$purchase->isValid()) {
                $result = $em->remove($purchase);
                if ($result['result']) {
                    if ($result['result'] && str_ends_with($request->headers->get('referer'), 'edit')) {
                        $result['redirect'] = $this->generateUrl(
                            'app_purchase_list',
                            [],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        );
                    }

                    $this->addFlash('success', 'Achat supprimé avec succès.');
                }
                return $this->json($result);
            }
            return $this->json([
                'result' => false,
                'message' => 'Un achat validé ne peut pas être supprimé.'
            ]);
        } else {
            return $this->json(['result' => false, 'message' => 'L\'achat est déjà supprimée']);
        }
    }

    #[Route('/{purchaseId}/edit', 'app_purchase_edit', ['purchaseId' => '\d+'])]
    public function edit(Request $request, EntityManager $em, Validate $validate, int $purchaseId): Response
    {
        /** @var Purchase $purchase */
        $purchase = $this->purchaseRepo->find($purchaseId);
        if (!$purchase) {
            $message = 'L\'achat est introuvable.';
            if ($request->isMethod('GET')) {
                $this->addFlash('danger', 'L\'achat est introuvable.');
                return $this->redirectToRoute('app_purchase_list');
            } else {
                return $this->json(['result' => false, 'message' => $message]);
            }
        }

        $marketUrl = $this->generateUrl('app_market_search', ['forBuy' => 1], UrlGeneratorInterface::ABSOLUTE_URL);
        $isOrder = $purchase->isOrder();

        $form = $this->createForm(PurchaseType::class, $purchase, [
            'marketUrl' => $marketUrl,
            'post' => $request->isMethod('POST')
        ])->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($isOrder != $purchase->isOrder()) {
                $purchase->setReceived(!$purchase->isOrder());
                foreach ($purchase->getItemsPurchase() as $itemPurchase) {
                    $itemPurchase->setReceived(!$purchase->isOrder());
                }
            }
            $result = $em->flush();
            if ($result['result']) {
                $result['message'] = 'Achat modifié avec succès';
            }
            return $this->json($result);
        } elseif ($form->isSubmitted()) {
            return $this->json(['result' => false, 'messages' => $validate->getFormErrors($form)]);
        }
        return $this->render('purchase/edit_or_view.html.twig', ['purchase' => $purchase, 'form' => $form]);
    }

    #[Route('/{purchaseId}/validate', 'app_purchase_validate', ['purchaseId' => '\d+'])]
    public function validatePurchase(EventDispatcherInterface $dispatcher, EntityManager $em, int $purchaseId): Response
    {
        /** @var Purchase $purchase */
        $purchase = $this->purchaseRepo->find($purchaseId);
        if (!$purchase) {
            $this->addFlash('danger', 'L\'achat est introuvable.');
            return $this->redirectToRoute('app_purchase_list');
        }

        if ($purchase->getItemsPurchase()->isEmpty()) {
            return $this->json(['result' => false, 'message' => 'L\'achat doit avoir au moins un objet.']);
        }

        $dateTime = new DateTime();
        $purchase->setIsValid(true);
        $purchase->setValidatedAt($dateTime);

        $event = new StateEvent($purchase->getId(), Purchase::class, 'validate', true);
        $dispatcher->dispatch($event, 'state');

        $result = $em->flush();
        if ($result['result']) {
            return $this->json($result);
        }
        return $this->redirectToRoute('app_purchase_view', ['purchaseId' => $purchaseId]);
    }

    #[Route('/{purchaseId}/view', 'app_purchase_view', ['purchaseId' => '\d+'])]
    public function view(int $purchaseId): Response
    {
        /** @var Purchase $purchase */
        $purchase = $this->purchaseRepo->find($purchaseId);
        if (!$purchase) {
            $this->addFlash('danger', 'L\'achat est introuvable.');
            return $this->redirectToRoute('app_purchase_list');
        }
        return $this->render('purchase/edit_or_view.html.twig', ['purchase' => $purchase]);
    }

    #[Route('/{purchaseId}/state', 'app_purchase_state', ['purchaseId' => '\d+'])]
    public function state(
        Request $request,
        Validate $validate,
        EventDispatcherInterface $dispatcher,
        EntityManager $em,
        int $purchaseId
    ): Response {
        /** @var Purchase $purchase */
        $purchase = $this->purchaseRepo->find($purchaseId);
        if (!$purchase) {
            return $this->json(['result' => false, 'message' => 'L\'achat est introuvable.']);
        }

        $data = $request->request->all();
        $state = $data['state'];
        if (!in_array($state, ['received', 'refunded', 'refundRequest'])) {
            return $this->json(['result' => false, 'message' => 'Une erreur est survenue']);
        }

        $method = 'set' . ucfirst($state);
        if (in_array($state, ['received', 'refunded'])) {
            if (empty($data['date'])) {
                return $this->json(['result' => false, 'messages' => ['date' => 'Veuillez choisir une date']]);
            }

            $dateString = str_replace('/', '-', $data['date']);
            $time = strtotime($dateString);
            $purchase->{$method . 'At'}(new DateTime(date('Y-m-d', $time)));
        }

        if ($state == 'refundRequest' && !empty($data['reason'])) {
            $purchase->setRefundedReason($data['reason']);
        }

        $purchase->{$method}(true);

        $violations = $validate->validate($purchase);
        if (!empty($violations)) {
            return $this->json(['result' => false, 'messages' => $violations]);
        }

        $event = new StateEvent($purchase->getId(), Purchase::class, $state, true);
        $dispatcher->dispatch($event, 'state');

        return $this->json($em->flush());
    }
}
