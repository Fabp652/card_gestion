<?php

namespace App\Controller;

use App\Entity\ItemQuality;
use App\Form\ItemQualityType;
use App\Repository\ItemQualityRepository;
use App\Repository\ItemRepository;
use App\Service\EntityManager;
use App\Service\FileManager;
use App\Service\Validate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ItemQualityController extends AbstractController
{
    private const FOLDER = 'itemQuality';

    public function __construct(private ItemQualityRepository $itemQualityRepository)
    {
    }

    #[Route('/item/quality/search', 'app_item_quality_search')]
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

    #[Route('/item/{itemId}/quality/add', 'app_item_quality_add', ['itemId' => '\d+'])]
    #[Route('/item/quality/{itemQualityId}/update', 'app_item_quality_edit', ['itemQualityId' => '\d+'])]
    public function form(
        Request $request,
        FileManager $fileManager,
        ItemRepository $itemRepo,
        EntityManager $em,
        Validate $validate,
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
                $item->addItemQuality($itemQuality);

                if ($item->getItemQualities()->count() > $item->getNumber()) {
                    $item->setNumber($item->getNumber() + 1);
                }
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

                    $result = $em->remove($file);
                    if (!$result['result']) {
                        return $this->json($result);
                    }
                }
            }

            if ($form->has('files')) {
                foreach ($form->get('files')->getData() as $file) {
                    $result = $fileManager->addOrReplace($file, self::FOLDER, $item->getName());

                    if (!$result['result']) {
                        return $this->json($result);
                    }

                    $fileManagerEntity = $result['newEntityFile'];
                    $item->addFile($fileManagerEntity);
                    $em->persist($fileManagerEntity);
                }
            }

            if (!$itemQuality->getSort()) {
                $itemQuality->setSort($item->getItemQualities()->count() + 1);
            }

            return $this->json($em->persist($itemQuality, true));
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            return $this->json(['result' => false, 'messages' => $validate->getFormErrors($form)]);
        }

        $render = $this->render('item/quality/form.html.twig', [
            'form' => $form->createView(),
            'itemId' => $itemId,
            'itemQualityId' => $itemQualityId,
            'itemQuality' => $itemQualityId ? $itemQuality : null
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route('/item/quality/{itemQualityId}/available', 'app_item_quality_available', ['itemQualityId' => '\d+'])]
    public function available(Request $request, EntityManager $em, int $itemQualityId): Response
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
            $result = $em->flush();
            if (!$result['result']) {
                return $this->json($result);
            }
        }
        return $this->json(['result' => true, 'message' => 'Mis à jour avec succès']);
    }
}
