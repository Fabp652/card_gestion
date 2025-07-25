<?php

namespace App\Controller;

use App\Entity\Sale;
use App\Event\StateEvent;
use App\Form\SaleType;
use App\Repository\SaleRepository;
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

final class SaleController extends AbstractController
{
    public function __construct(private SaleRepository $saleRepo)
    {
    }

    #[Route('/sale', name: 'app_sale_list')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $filters = $request->query->all('filter');
        $filters = array_filter($filters, function ($filter) {
            return !empty($filter) || $filter == 0;
        });

        $sales = $this->saleRepo->findByFilter($filters);
        $sales = $paginator->paginate($sales, $request->query->get('page', 1), $request->query->get('limit', 10));

        return $this->render('sale/index.html.twig', [
            'request' => $request,
            'sales' => $sales,
            'states' => $this->saleRepo->getStates()
        ]);
    }

    #[Route('/sale/add', 'app_sale_add')]
    public function add(Request $request, EntityManager $em, Validate $validate): Response
    {
        $sale = new Sale();
        $marketUrl = $this->generateUrl('app_market_search', ['forSale' => 1], UrlGeneratorInterface::ABSOLUTE_URL);

        $form = $this->createForm(SaleType::class, $sale, [
            'marketUrl' => $marketUrl,
            'post' => $request->isMethod('POST')
        ])->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$sale->getId()) {
                $result = $em->persist($sale, true);
            } else {
                $result = $em->flush();
            }

            if ($result['result']) {
                $result['redirect'] = $this->generateUrl('app_sale_edit', ['saleId' => $sale->getId()]);
            }
            return $this->json($result);
        } elseif ($form->isSubmitted()) {
            return $this->json(['result' => false, 'messages' => $validate->getFormErrors($form)]);
        }

        $render = $this->render('sale/form.html.twig', [
            'form' => $form->createView()
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route('/sale/{saleId}/edit', 'app_sale_edit', ['saleId' => '\d+'])]
    public function edit(Request $request, EntityManager $em, Validate $validate, int $saleId): Response
    {
        /** @var Sale $sale */
        $sale = $this->saleRepo->find($saleId);
        if (!$sale) {
            $message = 'L\'achat est introuvable.';
            if ($request->isMethod('GET')) {
                return $this->render('error/not_found.html.twig', [
                    'message' => $message
                ]);
            } else {
                return $this->json(['result' => false, 'message' => $message]);
            }
        }

        $marketUrl = $this->generateUrl('app_market_search', ['forSale' => 1], UrlGeneratorInterface::ABSOLUTE_URL);

        $form = $this->createForm(SaleType::class, $sale, [
            'marketUrl' => $marketUrl,
            'post' => $request->isMethod('POST')
        ])->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $result = $em->flush();
            $result['message'] = 'Achat modifié avec succès';
            return $this->json($result);
        } elseif ($form->isSubmitted()) {
            return $this->json(['result' => false, 'messages' => $validate->getFormErrors($form)]);
        }

        return $this->render('sale/edit_or_view.html.twig', [
            'sale' => $sale,
            'form' => $form
        ]);
    }

    #[Route('/sale/{saleId}/validate', 'app_sale_validate', ['saleId' => '\d+'])]
    public function validateSale(EventDispatcherInterface $dispatcher, EntityManager $em, int $saleId): Response
    {
        /** @var Sale $sale */
        $sale = $this->saleRepo->find($saleId);
        if (!$sale) {
            return $this->render('error/not_found.html.twig', ['message' => 'La vente est introuvable.']);
        }

        if ($sale->getItemSales()->isEmpty()) {
            $this->addFlash('danger', 'La vente doit avoir au moins un objet.');
            return $this->redirectToRoute('app_sale_edit', ['saleId' => $saleId]);
        }

        $dateTime = new DateTime();
        $sale->setIsValid(true);
        $sale->setValidatedAt($dateTime);

        $event = new StateEvent(
            $sale->getId(),
            Sale::class,
            'validate',
            true
        );

        $dispatcher->dispatch($event, 'state');

        $result = $em->flush();
        if (!$result['result']) {
            return $this->redirectToRoute('app_sale_edit', ['saleId' => $saleId]);
        }

        return $this->redirectToRoute('app_sale_view', ['saleId' => $saleId]);
    }

    #[Route('/sale/{saleId}/view', 'app_sale_view', ['saleId' => '\d+'])]
    public function view(int $saleId): Response
    {
        /** @var Sale $sale */
        $sale = $this->saleRepo->find($saleId);
        if (!$sale) {
            return $this->render('error/not_found.html.twig', [
                'message' => 'L\'achat est introuvable.'
            ]);
        }

        return $this->render('sale/edit_or_view.html.twig', [
            'sale' => $sale
        ]);
    }

    #[Route('/sale/{saleId}/state', 'app_sale_state', ['saleId' => '\d+'])]
    public function state(
        Request $request,
        Validate $validate,
        EventDispatcherInterface $dispatcher,
        EntityManager $em,
        int $saleId
    ): Response {
        /** @var Sale $sale */
        $sale = $this->saleRepo->find($saleId);
        if (!$sale) {
            return $this->render('error/not_found.html.twig', ['message' => 'L\'achat est introuvable.']);
        }

        $data = $request->request->all();
        $state = $data['state'];
        if (!in_array($state, ['send', 'refunded', 'refundRequest', 'sold'])) {
            return $this->json(['result' => false, 'message' => 'Une erreur est survenue']);
        }

        $method = 'set' . ucfirst($state);
        if (in_array($state, ['send', 'refunded'])) {
            if (empty($data['date'])) {
                return $this->json(['result' => false, 'messages' => ['date' => 'Veuillez choisir une date']]);
            }

            $dateString = str_replace('/', '-', $data['date']);
            $time = strtotime($dateString);
            $sale->{$method . 'At'}(new DateTime(date('Y-m-d', $time)));
        }

        if ($state == 'refundRequest' && !empty($data['reason'])) {
            $sale->setRefundReason($data['reason']);
        }

        $sale->{$method}(true);

        $violations = $validate->validate($sale);
        if (!empty($violations)) {
            return $this->json(['result' => false, 'messages' => $violations]);
        }

        $event = new StateEvent($sale->getId(), Sale::class, $state, true);
        $dispatcher->dispatch($event, 'state');

        return $this->json($em->flush());
    }

    #[Route('/sale/{saleId}/delete', 'app_sale_delete', ['saleId' => '\d+'])]
    public function delete(Request $request, EntityManager $em, int $saleId): Response
    {
        /** @var Sale $sale */
        $sale = $this->saleRepo->find($saleId);
        if ($sale) {
            if (!$sale->isValid()) {
                $result = $em->remove($sale, true);
                if ($result['result'] && str_ends_with($request->headers->get('referer'), 'edit')) {
                    $response['redirect'] = $this->generateUrl(
                        'app_sale_list',
                        [],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
                }
                return $this->json($response);
            }
            return $this->json(['result' => false, 'message' => 'Une vente validé ne peut pas être supprimée.']);
        } else {
            return $this->json(['result' => false, 'message' => 'La vente est déjà supprimée']);
        }
    }
}
