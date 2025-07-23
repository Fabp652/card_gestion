<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Collections;
use App\Form\CollectionType;
use App\Repository\CollectionsRepository;
use App\Repository\ItemRepository;
use App\Repository\RarityRepository;
use App\Service\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CollectionController extends AbstractController
{
    private const FOLDER = 'collection';

    public function __construct(
        private CollectionsRepository $collectionRepo,
        private EntityManagerInterface $em
    ) {
    }

    #[Route(name: 'app_collection')]
    public function index(): Response
    {
        $stats = $this->collectionRepo->stats();

        return $this->render(
            'collection/index.html.twig',
            [
                'stats' => $stats
            ]
        );
    }

    #[Route(
        '/collection/{collectionId}',
        name: 'app_collection_view',
        requirements: ['collectionId' => '\d+']
    )]
    public function view(
        ItemRepository $itemRepo,
        RarityRepository $rarityRepository,
        int $collectionId
    ): Response {
        $collection = $this->collectionRepo->find($collectionId);
        $categories = $collection->getCategory()->getChilds();

        if ($collection->hasRarities()) {
            $statRarities = $rarityRepository->stats($collectionId);
        }

        $mostExpensives = [];
        foreach ($categories as $category) {
            $index = $category->getName() . '_' . $category->getId();
            $mostExpensives[$index] = $itemRepo->findMostExpensives($collectionId, $category->getId());
        }

        return $this->render('collection/view.html.twig', [
            'statRarities' => $statRarities ?? null,
            'mostExpensives' => $mostExpensives,
            'collection' => $collection
        ]);
    }

    #[Route(
        '/collection/{collectionId}/dropdown',
        name: 'app_collection_dropdown',
        requirements: ['collectionId' => '\d+']
    )]
    public function dropdown(int $collectionId): Response
    {
        $actualCollection = $this->collectionRepo->find($collectionId);

        $collections = $this->collectionRepo->findCollectionsWithoutActual($collectionId);

        return $this->render('collection/partial/dropdown.html.twig', [
            'actualCollection' => $actualCollection,
            'collections' => $collections
        ]);
    }

    #[Route('/collection/add', name: 'app_collection_add')]
    #[Route(
        '/collection/{collectionId}/edit',
        name: 'app_collection_edit',
        requirements: ['collectionId' => '\d+']
    )]
    public function form(
        Request $request,
        FileManager $fileManager,
        ValidatorInterface $validator,
        ?int $collectionId
    ): Response {
        if ($collectionId) {
            $collection = $this->collectionRepo->find($collectionId);
        } else {
            $collection = new Collections();
        }

        $form = $this->createForm(
            CollectionType::class,
            $collection,
            ['post' => $request->isMethod('POST')]
        )->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$collection->getCategory() && $categoryData = $form->get('category')->getData()) {
                $category = new Category();
                $category->setName($categoryData);

                $violations = $validator->validate($category);
                $errors = $this->getViolationsMessage($violations);

                if (!empty($errors)) {
                    $messages = $this->getFormErrors($form);
                    $messages['category'] = $errors['name'];

                    return $this->json(['result' => false, 'messages' => $messages]);
                }

                $this->em->persist($category);
                $collection->setCategory($category);
            } elseif (!$form->isValid()) {
                $messages = $this->getFormErrors($form);
                return $this->json(['result' => false, 'messages' => $messages]);
            }

            $file = $form->get('file')->getData();
            if ($file) {
                if ($collection->getFile()) {
                    $result = $fileManager->removeFile(
                        $collection->getFile()->getName(),
                        $collection->getFile()->getFolder()
                    );
                    if (!$result) {
                        return $this->json([
                            'result' => false,
                            'message' => 'Une erreur est survenue lors de l\'ajout du fichier.'
                        ]);
                    }

                    $this->em->remove($collection->getFile());
                }

                $fileManagerEntity = $fileManager->upload(self::FOLDER, $collection->getName(), $file);
                if (!$fileManagerEntity) {
                    return $this->json([
                        'result' => false,
                        'message' => 'Une erreur est survenue lors de l\'ajout du fichier.'
                    ]);
                }
                $this->em->persist($fileManagerEntity);
                $collection->setFile($fileManagerEntity);
            }

            $violations = $validator->validate($collection);
            if ($violations->count() > 0) {
                $messages = $this->getViolationsMessage($violations);
                return $this->json(['result' => false, 'messages' => $messages]);
            }

            if (!$collection->getId()) {
                $this->em->persist($collection);
            }
            $this->em->flush();

            return $this->json(['result' => true]);
        }

        $render = $this->render('collection/form.html.twig', [
            'form' => $form->createView(),
            'collectionId' => $collectionId,
            'file' => $collection->getFile()
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route(
        '/collection/{collectionId}/delete',
        name: 'app_collection_delete',
        requirements: ['collectionId' => '\d+']
    )]
    public function delete(int $collectionId): Response
    {
        $collection = $this->collectionRepo->find($collectionId);
        if ($collection) {
            if ($collection->getItems()->isEmpty()) {
                $this->em->remove($collection);
                $this->em->flush();

                return $this->json(['result' => true]);
            }
            return $this->json([
                'result' => false,
                'message' => 'La collection ne peut pas être supprimée si elle contient des objets.'
            ]);
        } else {
            return $this->json(['result' => false, 'message' => 'La collection est déjà supprimée']);
        }
    }

    #[Route(
        '/collection/{collectionId}/complete',
        'app_collection_complete',
        ['collectionId' => '\d+']
    )]
    public function complete(Request $request, int $collectionId): Response
    {
        /** @var Collections $collection */
        $collection = $this->collectionRepo->find($collectionId);
        if (!$collection) {
            return $this->json(['result' => false, 'message' => 'Une erreur est survenue.']);
        }

        $flush = false;
        $complete = $request->request->has('complete') ?
            $request->request->get('complete') == 'true' : null
        ;
        if (is_bool($complete) && $complete != $collection->isComplete()) {
            $collection->setComplete($complete);
            $flush = true;
        }

        if ($flush) {
            $this->em->flush();
        }

        return $this->json(['result' => true, 'message' => 'Mis à jour avec succès']);
    }

    private function getViolationsMessage(ConstraintViolationListInterface $violations): array
    {
        $messages = [];
        if ($violations->count() > 0) {
            foreach ($violations as $violation) {
                $messages[$violation->getPropertyPath()] = $violation->getMessage();
            }
        }

        return $messages;
    }

    private function getFormErrors(FormInterface $form): array
    {
        $messages = [];
        foreach ($form->getErrors(true) as $error) {
            $field = $error->getOrigin()->getName();
            $messages[$field] = $error->getMessage();
        }

        return $messages;
    }
}
