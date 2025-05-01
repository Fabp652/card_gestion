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
        if ($search = $request->query->get('search')) {
            $storageId = (int) $request->query->get('storageId');
            $notSale = $request->query->get('notSale') == 1;

            $items = $this->itemQualityRepository->search($search, $storageId, $notSale);

            return $this->json(['result' => true, 'searchResults' => $items]);
        }

        return $this->json(['result' => false]);
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

            if ($form->has('file')) {
                $file = $form->get('file')->getData();
                if ($file) {
                    if ($itemQuality->getFile()) {
                        $result = $fileManager->removeFile(
                            $itemQuality->getFile()->getName(),
                            $itemQuality->getFile()->getFolder()
                        );
                        if (!$result) {
                            return $this->json([
                                'result' => false,
                                'message' => 'Une erreur est survenue lors de l\'ajout du fichier.'
                            ]);
                        }
                    }
                    $fileManagerEntity = $fileManager->upload('item', $item->getName(), $file);

                    if (!$fileManagerEntity) {
                        return $this->json([
                            'result' => false,
                            'message' => 'Une erreur est survenue pendant le téléchargement du fichier.'
                        ]);
                    }
                    $this->em->persist($fileManagerEntity);
                    $itemQuality->setFile($fileManagerEntity);
                }
            }

            if (!$itemQuality->getSort()) {
                $itemQuality->setSort($item->getItemQualities()->count() + 1);
            }

            $this->em->persist($itemQuality);
            $this->em->flush();
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $messages = [];
            foreach ($form->getErrors(true) as $error) {
                $propertyPath = $error->getCause()->getPropertyPath();
                $propertyPathExplode = explode('.', $propertyPath);
                $messages[$propertyPathExplode[1]] = $error->getMessage();
            }
            return $this->json(['result' => false, 'messages' => $messages]);
        }

        $render = $this->render('item/quality/form.html.twig', [
            'form' => $form->createView(),
            'itemId' => $itemId,
            'itemQualityId' => $itemQualityId
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }
}
