<?php

namespace App\Controller;

use App\Entity\Purchase;
use App\Event\StateEvent;
use App\Form\PurchaseType;
use App\Repository\PurchaseRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        $marketUrl = $this->generateUrl(
            'app_market_search',
            ['forBuy' => 1],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $form = $this->createForm(
            PurchaseType::class,
            $purchase,
            [
                'marketUrl' => $marketUrl,
                'post' => $request->isMethod('POST')
            ]
        )->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$purchase->isOrder()) {
                $purchase->setReceived(true);
            }

            $this->em->persist($purchase);
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
        'app_purchase_delete',
        ['purchaseId' => '\d+']
    )]
    public function delete(Request $request, int $purchaseId): Response
    {
        $purchase = $this->purchaseRepo->find($purchaseId);
        if ($purchase) {
            $this->em->remove($purchase);
            $this->em->flush();

            $response = ['result' => true];
            if (str_ends_with($request->headers->get('referer'), 'edit')) {
                $response['redirect'] = $this->generateUrl(
                    'app_purchase_list',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            }

            return $this->json($response);
        } else {
            return $this->json(['result' => false, 'message' => 'L\'achat est déjà supprimée']);
        }
    }

    #[Route(
        '/purchase/{purchaseId}/edit',
        'app_purchase_edit',
        ['purchaseId' => '\d+']
    )]
    public function edit(Request $request, int $purchaseId): Response
    {
        /** @var Purchase $purchase */
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

        $marketUrl = $this->generateUrl(
            'app_market_search',
            ['forBuy' => 1],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $isOrder = $purchase->isOrder();

        $form = $this->createForm(
            PurchaseType::class,
            $purchase,
            [
                'marketUrl' => $marketUrl,
                'post' => $request->isMethod('POST')
            ]
        )->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($isOrder != $purchase->isOrder()) {
                $purchase->setReceived(!$purchase->isOrder());
                foreach ($purchase->getItemsPurchase() as $itemPurchase) {
                    $itemPurchase->setReceived(!$purchase->isOrder());
                }
            }
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

    #[Route(
        '/purchase/{purchaseId}/validate',
        'app_purchase_validate',
        ['purchaseId' => '\d+']
    )]
    public function validatePurchase(EventDispatcherInterface $dispatcher, int $purchaseId): Response
    {
        /** @var Purchase $purchase */
        $purchase = $this->purchaseRepo->find($purchaseId);
        if (!$purchase) {
            return $this->render('error/not_found.html.twig', [
                'message' => 'L\'achat est introuvable.'
            ]);
        }

        if ($purchase->getItemsPurchase()->isEmpty()) {
            return $this->json(['result' => false, 'message' => 'L\'achat doit avoir au moins un objet.']);
        }

        $dateTime = new DateTime();
        $purchase->setIsValid(true);
        $purchase->setValidatedAt($dateTime);

        $event = new StateEvent(
            $purchase->getId(),
            Purchase::class,
            'validate',
            true
        );

        $dispatcher->dispatch($event, 'state');

        $this->em->flush();

        return $this->redirectToRoute('app_purchase_view', ['purchaseId' => $purchaseId]);
    }

    #[Route(
        '/purchase/{purchaseId}/view',
        'app_purchase_view',
        ['purchaseId' => '\d+']
    )]
    public function view(int $purchaseId): Response
    {
        /** @var Purchase $purchase */
        $purchase = $this->purchaseRepo->find($purchaseId);
        if (!$purchase) {
            return $this->render('error/not_found.html.twig', [
                'message' => 'L\'achat est introuvable.'
            ]);
        }

        return $this->render('purchase/edit_or_view.html.twig', [
            'purchase' => $purchase
        ]);
    }

    #[Route(
        '/purchase/{purchaseId}/state',
        'app_purchase_state',
        ['purchaseId' => '\d+']
    )]
    public function state(
        Request $request,
        ValidatorInterface $validator,
        EventDispatcherInterface $dispatcher,
        int $purchaseId
    ): Response {
        /** @var Purchase $purchase */
        $purchase = $this->purchaseRepo->find($purchaseId);
        if (!$purchase) {
            return $this->render('error/not_found.html.twig', [
                'message' => 'L\'achat est introuvable.'
            ]);
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

        $violations = $validator->validate($purchase);
        if ($violations->count() > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->json(['result' => false, 'messages' => $messages]);
        }

        $event = new StateEvent(
            $purchase->getId(),
            Purchase::class,
            $state,
            true
        );

        $dispatcher->dispatch($event, 'state');
        $this->em->flush();

        return $this->json(['result' => true]);
    }
}
