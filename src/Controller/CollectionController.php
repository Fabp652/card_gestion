<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Collections;
use App\Form\CollectionType;
use App\Repository\CollectionsRepository;
use App\Repository\ItemRepository;
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
        private ItemRepository $itemRepo,
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
    public function view(int $collectionId): Response
    {
        $collection = $this->collectionRepo->find($collectionId);
        $categories = $collection->getCategory()->getChilds();

        if (!$collection->getRarities()->isEmpty()) {
            $statRarities = $this->itemRepo->statByRarity($collectionId);
        }

        $mostExpensives = [];
        foreach ($categories as $category) {
            $index = $category->getName() . '_' . $category->getId();
            $mostExpensives[$index] = $this->itemRepo->findMostExpensives($collectionId, $category->getId());
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

        $form = $this->createForm(CollectionType::class, $collection)->handleRequest($request);

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
            $result = $this->createOrUpdate($collection, $fileManager, $file);
            return $this->json($result);
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
            $this->em->remove($collection);
            $this->em->flush();

            return $this->json(['result' => true]);
        } else {
            return $this->json(['result' => false, 'message' => 'La collection est déjà supprimée']);
        }
    }

    private function createOrUpdate(
        Collections $collection,
        FileManager $fileManager,
        ?UploadedFile $file,
        ?string $categoryName = null
    ): array {
        if ($file) {
            if ($collection->getFile()) {
                $result = $fileManager->removeFile(
                    $collection->getFile()->getName(),
                    $collection->getFile()->getFolder()
                );
                if (!$result) {
                    return [
                        'result' => false,
                        'message' => 'Une erreur est survenue lors de l\'ajout du fichier.'
                    ];
                }
            }

            $fileManagerEntity = $fileManager->upload(self::FOLDER, $collection->getName(), $file);
            if (!$fileManagerEntity) {
                return [
                    'result' => false,
                    'message' => 'Une erreur est survenue lors de l\'ajout du fichier.'
                ];
            }
            $this->em->persist($fileManagerEntity);
            $collection->setFile($fileManagerEntity);
        }

        if ($categoryName) {
            $category = new Category();
            $category->setName($categoryName);
            $this->em->persist($category);

            $collection->setCategory($category);
        }

        if (!$collection->getId()) {
            $this->em->persist($collection);
        }
        $this->em->flush();

        return ['result' => true];
    }

    private function getViolationsMessage(ConstraintViolationListInterface $violations)
    {
        $messages = [];
        if ($violations->count() > 0) {
            foreach ($violations as $violation) {
                $messages[$violation->getPropertyPath()] = $violation->getMessage();
            }
        }

        return $messages;
    }

    private function getFormErrors(FormInterface $form)
    {
        $messages = [];
        foreach ($form->getErrors(true) as $error) {
            $propertyPath = $error->getCause()->getPropertyPath();
            $propertyPathExplode = explode('.', $propertyPath);
            $messages[$propertyPathExplode[1]] = $error->getMessage();
        }

        return $messages;
    }
}
