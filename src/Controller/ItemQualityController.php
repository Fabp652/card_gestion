<?php

namespace App\Controller;

use App\Entity\ItemQuality;
use App\Form\ItemQualityType;
use App\Repository\ItemQualityRepository;
use App\Repository\ItemRepository;
use App\Service\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ItemQualityController extends AbstractController
{
    public function __construct(
        private ItemQualityRepository $itemQualityRepository,
        private EntityManagerInterface $em
    ) {
    }

    #[Route('/item/quality/search', name: 'app_item_quality_search')]
    public function search(Request $request): Response
    {
        $filters = $request->query->all() ;

        $concat = "CASE WHEN i.reference IS NOT NULL THEN CONCAT('N°', iq.sort, ' ', i.reference, ' - ', i.name, ";
        $concat .= "' (', c.name, ')') ELSE CONCAT('N°', iq.sort, ' ', i.name, ' (', c.name, ')') END AS text";
        $items = $this->itemQualityRepository->findByFilter($filters);
        $items->leftJoin('i.collection', 'c')
            ->select('iq.id', $concat)
            ->setMaxResults(30)
        ;

        return $this->json(['result' => true, 'searchResults' => $items->getQuery()->getResult()]);
    }

    #[Route(
        '/item/{itemId}/quality/add',
        name: 'app_item_quality_add',
        requirements: ['itemId' => '\d+']
    )]
    #[Route(
        '/item/quality/{itemQualityId}/update',
        name: 'app_item_quality_edit',
        requirements: ['itemQualityId' => '\d+']
    )]
    public function form(
        Request $request,
        FileManager $fileManager,
        ItemRepository $itemRepo,
        ?int $itemId,
        ?int $itemQualityId
    ): Response {
        $options = [];
        if ($itemQualityId) {
            $itemQuality = $this->itemQualityRepository->find($itemQualityId);
            $item = $itemQuality->getItem();
        } else {
            $item = $itemRepo->find($itemId);
            $itemQuality = new ItemQuality();
        }
        $options['category'] = $item->getCategory();

        $form = $this->createForm(
            ItemQualityType::class,
            $itemQuality,
            $options
        )->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($request->get('perfect')) {
                $itemQuality->setQuality(10);
            } elseif ($itemQuality->getCriterias()->isEmpty()) {
                $itemQuality->setQuality(null);
            } elseif ($itemQuality->getQuality() < 0) {
                $itemQuality->setQuality(0);
            }

            if (!$itemQuality->getId()) {
                $itemQuality->setItem($item);
            }

            if ($request->request->has('removeFiles')) {
                $removeFiles = explode(',', $request->request->get('removeFiles'));
                foreach ($removeFiles as $removeFile) {
                    $file = $fileManager->removeFileById((int) $removeFile);
                    if (!$file) {
                        return $this->json([
                            'result' => false,
                            'message' => 'Une erreur est survenue lors de la suppression du fichier.'
                        ]);
                    }

                    $this->em->remove($file);
                }
            }

            if ($form->has('files')) {
                foreach ($form->get('files')->getData() as $file) {
                    $fileManagerEntity = $fileManager->upload('itemQuality', $item->getName(), $file);

                    if (!$fileManagerEntity) {
                        return $this->json([
                            'result' => false,
                            'message' => 'Une erreur est survenue pendant le téléchargement du fichier.'
                        ]);
                    }
                    $this->em->persist($fileManagerEntity);
                    $itemQuality->addFile($fileManagerEntity);
                }
            }

            if (!$itemQuality->getSort()) {
                $itemQuality->setSort($item->getItemQualities()->count() + 1);
            }

            $item->setNumber($item->getNumber() + 1);

            $this->em->persist($itemQuality);
            $this->em->flush();
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $messages = [];
            foreach ($form->getErrors(true) as $error) {
                $field = $error->getOrigin()->getName();
                $messages[$field] = $error->getMessage();
            }
            return $this->json(['result' => false, 'messages' => $messages]);
        }

        $render = $this->render('item/quality/form.html.twig', [
            'form' => $form->createView(),
            'itemId' => $itemId,
            'itemQualityId' => $itemQualityId,
            'itemQuality' => $itemQualityId ? $itemQuality : null
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route(
        '/item/quality/{itemQualityId}/available',
        'app_item_quality_available',
        ['itemQualityId' => '\d+']
    )]
    public function available(Request $request, int $itemQualityId): Response
    {
        /** @var ItemQuality $itemQuality */
        $itemQuality = $this->itemQualityRepository->find($itemQualityId);
        if (!$itemQuality) {
            return $this->json(['result' => false, 'message' => 'Une erreur est survenue.']);
        }

        $flush = false;
        $availableSale = $request->request->has('availableSale') ?
            $request->request->get('availableSale') == 'true' : null
        ;
        if (is_bool($availableSale) && $availableSale != $itemQuality->isAvailableSale()) {
            $itemQuality->setAvailableSale($availableSale);
            $flush = true;
        }

        if ($flush) {
            $this->em->flush();
        }

        return $this->json(['result' => true, 'message' => 'Mis à jour avec succès']);
    }
}
