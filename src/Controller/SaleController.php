<?php

namespace App\Controller;

use App\Entity\Sale;
use App\Form\SaleType;
use App\Repository\SaleRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SaleController extends AbstractController
{
    public function __construct(private SaleRepository $saleRepo, private EntityManagerInterface $em)
    {
    }

    #[Route('/sale', name: 'app_sale_list')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $filters = $request->query->all('filter');
        $filters = array_filter(
            $filters,
            function ($filter) {
                return !empty($filter) || $filter == 0;
            }
        );

        $sales = $this->saleRepo->findByFilter($filters);

        $sales = $paginator->paginate(
            $sales,
            $request->query->get('page', 1),
            $request->query->get('limit', 10)
        );

        return $this->render('sale/index.html.twig', [
            'request' => $request,
            'sales' => $sales,
            'states' => $this->saleRepo->getStates()
        ]);
    }

    #[Route('/sale/add', 'app_sale_add')]
    public function add(Request $request): Response
    {
        $sale = new Sale();

        $marketUrl = $this->generateUrl('app_market_search', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $form = $this->createForm(SaleType::class, $sale, ['marketUrl' => $marketUrl])->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($sale);
            $this->em->flush();

            return $this->json([
                'result' => true,
                'redirect' => $this->generateUrl('app_sale_edit', ['saleId' => $sale->getId()])
            ]);
        } elseif ($form->isSubmitted()) {
            $messages = [];
            foreach ($form->getErrors(true) as $error) {
                $field = $error->getOrigin()->getName();
                $messages[$field] = $error->getMessage();
            }
            return $this->json(['result' => false, 'messages' => $messages]);
        }

        $render = $this->render('sale/form.html.twig', [
            'form' => $form->createView()
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route(
        '/sale/{saleId}/edit',
        'app_sale_edit',
        ['saleId' => '\d+']
    )]
    public function edit(Request $request, int $saleId): Response
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

        $marketUrl = $this->generateUrl('app_market_search', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $form = $this->createForm(SaleType::class, $sale, ['marketUrl' => $marketUrl])->handleRequest($request);
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

        return $this->render('sale/edit_or_view.html.twig', [
            'sale' => $sale,
            'form' => $form
        ]);
    }

    #[Route(
        '/sale/{saleId}/validate',
        'app_sale_validate',
        ['saleId' => '\d+']
    )]
    public function validate(int $saleId): Response
    {
        /** @var Sale $sale */
        $sale = $this->saleRepo->find($saleId);
        if (!$sale) {
            return $this->render('error/not_found.html.twig', [
                'message' => 'La vente est introuvable.'
            ]);
        }

        if ($sale->getItemSales()->isEmpty()) {
            return $this->json(['result' => false, 'message' => 'La vente doit avoir au moins un objet.']);
        }

        $dateTime = new DateTime();
        $sale->setIsValid(true);
        $sale->setValidatedAt($dateTime);

        $this->em->flush();

        return $this->redirectToRoute('app_sale_view', ['saleId' => $saleId]);
    }

    #[Route(
        '/sale/{saleId}/view',
        'app_sale_view',
        ['saleId' => '\d+']
    )]
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

    #[Route(
        '/sale/{saleId}/state',
        'app_sale_state',
        ['saleId' => '\d+']
    )]
    public function state(Request $request, int $saleId): Response
    {
        /** @var Sale $sale */
        $sale = $this->saleRepo->find($saleId);
        if (!$sale) {
            return $this->render('error/not_found.html.twig', [
                'message' => 'L\'achat est introuvable.'
            ]);
        }

        $data = $request->request->all();
        switch ($data['state']) {
            case 'sold':
                if (empty($data['date'])) {
                    return $this->json(['result' => false, 'messages' => ['date' => 'Veuillez choisir une date']]);
                }

                $dateString = str_replace('/', '-', $data['date']);
                $time = strtotime($dateString);
                $sale->setSold(true)
                    ->setSoldAt(new DateTime(date('Y-m-d', $time)))
                ;
                break;

            case 'refundRequest':
                $sale->setRefundRequest(true);
                if (!empty($data['reason'])) {
                    $sale->setRefundReason($data['reason']);
                }

                foreach ($sale->getItemSales() as $itemSale) {
                    if (!$itemSale->isRefundRequest()) {
                        $itemSale->setRefundRequest(true);
                    }
                }
                break;

            case 'refunded':
                if (empty($data['date'])) {
                    return $this->json(['result' => false, 'messages' => ['date' => 'Veuillez choisir une date']]);
                }

                $dateString = str_replace('/', '-', $data['date']);
                $time = strtotime($dateString);
                $dateTime = new DateTime(date('Y-m-d', $time));
                $sale->setRefunded(true)
                    ->setRefundAt($dateTime)
                ;

                foreach ($sale->getItemSales() as $itemSale) {
                    if (!$itemSale->isRefunded()) {
                        $itemSale->setRefunded(true)
                            ->setRefundAt($dateTime);
                        ;
                    }
                }
                break;

            case 'send':
                if (empty($data['date'])) {
                    return $this->json(['result' => false, 'messages' => ['date' => 'Veuillez choisir une date']]);
                }

                $dateString = str_replace('/', '-', $data['date']);
                $time = strtotime($dateString);
                $dateTime = new DateTime(date('Y-m-d', $time));
                $sale->setSend(true)
                    ->setSendAt($dateTime)
                ;

                foreach ($sale->getItemSales() as $itemSale) {
                    if (!$itemSale->isSend()) {
                        $itemSale->setSend(true)
                            ->setSendAt($dateTime);
                        ;
                    }
                }
                break;
            default:
                return $this->json(['result' => false, 'message' => 'Une erreur est survenue']);
                break;
        }
        $this->em->flush();

        return $this->json(['result' => true]);
    }

    #[Route(
        '/sale/{saleId}/delete',
        'app_sale_delete',
        ['saleId' => '\d+']
    )]
    public function delete(Request $request, int $saleId): Response
    {
        /** @var Sale $sale */
        $sale = $this->saleRepo->find($saleId);
        if ($sale) {
            foreach ($sale->getItemSales() as $itemSale) {
                $itemSale->setItemQuality(null);
                $this->em->remove($itemSale);
            }
            $this->em->remove($sale);
            $this->em->flush();

            $response = ['result' => true];
            if (str_ends_with($request->headers->get('referer'), 'edit')) {
                $response['redirect'] = $this->generateUrl(
                    'app_sale_list',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            }

            return $this->json($response);
        } else {
            return $this->json(['result' => false, 'message' => 'La vente est déjà supprimée']);
        }
    }
}
