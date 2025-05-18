<?php

namespace App\Controller;

use App\Entity\Rarity;
use App\Form\RarityType;
use App\Repository\CollectionsRepository;
use App\Repository\RarityRepository;
use App\Service\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RarityController extends AbstractController
{
    private const FOLDER = 'rarity';

    public function __construct(private RarityRepository $rarityRepository, private EntityManagerInterface $em)
    {
    }

    #[Route(
        '/collection/{collectionId}/rarity/add',
        name: 'app_rarity_add',
        requirements: ['collectionId' => '\d+']
    )]
    #[Route(
        '/rarity/{rarityId}/edit',
        name: 'app_rarity_edit',
        requirements: ['rarityId' => '\d+']
    )]
    public function form(
        Request $request,
        CollectionsRepository $collectionsRepository,
        FileManager $fileManager,
        ?int $collectionId,
        ?int $rarityId
    ): Response {
        if ($rarityId) {
            $rarity = $this->rarityRepository->find($rarityId);
        } else {
            $rarity = new Rarity();
        }

        if ($collectionId) {
            $collection = $collectionsRepository->find($collectionId);
            $rarity->setCollection($collection);
        }

        $form = $this->createForm(RarityType::class, $rarity)->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            if ($file) {
                if ($rarity->getFile()) {
                    $result = $fileManager->removeFile(
                        $rarity->getFile()->getName(),
                        $rarity->getFile()->getFolder()
                    );
                    if (!$result) {
                        return $this->json([
                            'result' => false,
                            'message' => 'Une erreur est survenue lors de l\'ajout du fichier.'
                        ]);
                    }
                }

                $fileManagerEntity = $fileManager->upload(self::FOLDER, $rarity->getName(), $file);
                if (!$fileManagerEntity) {
                    return $this->json([
                        'result' => false,
                        'message' => 'Une erreur est survenue lors de l\'ajout du fichier.'
                    ]);
                }
                $this->em->persist($fileManagerEntity);
                $rarity->setFile($fileManagerEntity);
            }
            $this->em->persist($rarity);
            $this->em->flush();

            return $this->json(['result' => true]);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $messages = [];
            foreach ($form->getErrors(true) as $error) {
                $field = $error->getOrigin()->getName();
                $messages[$field] = $error->getMessage();
            }
            return $this->json(['result' => false, 'messages' => $messages]);
        }

        $render = $this->render('rarity/form.html.twig', [
            'form' => $form->createView(),
            'rarityId' => $rarityId,
            'collectionId' => $collectionId,
            'file' => $rarity->getFile()
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route(
        '/rarity/{rarityId}/delete',
        name: 'app_rarity_delete',
        requirements: ['rarityId' => '\d+']
    )]
    public function delete(int $rarityId): Response
    {
        $rarity = $this->rarityRepository->find($rarityId);
        if ($rarity) {
            $this->em->remove($rarity);
            $this->em->flush();

            return $this->json(['result' => true]);
        } else {
            return $this->json(['result' => false, 'message' => 'La rarité est déjà supprimée']);
        }
    }
}
