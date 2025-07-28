<?php

namespace App\Controller;

use App\Entity\Rarity;
use App\Form\RarityType;
use App\Repository\CollectionsRepository;
use App\Repository\RarityRepository;
use App\Service\EntityManager;
use App\Service\FileManager;
use App\Service\Validate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RarityController extends AbstractController
{
    private const FOLDER = 'rarity';

    public function __construct(private RarityRepository $rarityRepository, private EntityManager $em)
    {
    }

    #[Route('/collection/{collectionId}/rarity/add', 'app_rarity_add', ['collectionId' => '\d+'])]
    #[Route('/rarity/{rarityId}/edit', 'app_rarity_edit', ['rarityId' => '\d+'])]
    public function form(
        Request $request,
        CollectionsRepository $collectionsRepository,
        FileManager $fileManager,
        Validate $validate,
        ?int $collectionId,
        ?int $rarityId
    ): Response {
        $rarity = new Rarity();
        if ($rarityId) {
            $rarity = $this->rarityRepository->find($rarityId);
        }

        if ($collectionId) {
            $collection = $collectionsRepository->find($collectionId);
            $rarity->setCollection($collection);
        }

        $form = $this->createForm(RarityType::class, $rarity)->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            if ($file) {
                $result = $fileManager->addOrReplace($file, self::FOLDER, $rarity->getName(), $rarity->getFile());
                if (!$result['result']) {
                    return $this->json($result);
                }
                if ($rarity->getFile()) {
                    $this->em->remove($rarity->getFile());
                }

                $fileManagerEntity = $result['newEntityFile'];
                $this->em->persist($fileManagerEntity);
                if (!$result['result']) {
                    return $this->json($result);
                }
                $rarity->setFile($fileManagerEntity);

                $this->addFlash('success', 'Fichier ajouté avec succès.');
            }

            if (!$rarity->getId()) {
                $addOrUpdateMessage = 'ajoutée';
                $result = $this->em->persist($rarity, true);
            } else {
                $addOrUpdateMessage = 'modifiée';
                $result = $this->em->flush();
            }

            if ($result['result']) {
                $this->addFlash('success', 'Rareté ' . $addOrUpdateMessage . ' avec succès.');
            }
            return $this->json($result);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            return $this->json(['result' => false, 'messages' => $validate->getFormErrors($form)]);
        }

        $render = $this->render('rarity/form.html.twig', [
            'form' => $form->createView(),
            'rarityId' => $rarityId,
            'collectionId' => $collectionId,
            'file' => $rarity->getFile()
        ]);
        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route('/rarity/{rarityId}/delete', 'app_rarity_delete', ['rarityId' => '\d+'])]
    public function delete(int $rarityId): Response
    {
        $rarity = $this->rarityRepository->find($rarityId);
        if ($rarity) {
            $result = $this->em->remove($rarity, true);
            if ($result['result']) {
                $this->addFlash('success', 'Rareté supprimé avec succès.');
            }
            return $this->json($result);
        } else {
            return $this->json(['result' => false, 'message' => 'La rarité est déjà supprimée']);
        }
    }
}
