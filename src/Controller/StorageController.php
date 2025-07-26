<?php

namespace App\Controller;

use App\Entity\Storage;
use App\Form\StorageType;
use App\Repository\ItemQualityRepository;
use App\Repository\StorageRepository;
use App\Service\EntityManager;
use App\Service\Validate;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StorageController extends AbstractController
{
    public function __construct(private StorageRepository $storageRepository)
    {
    }

    #[Route('/storage', 'app_storage_list')]
    public function list(): Response
    {
        $stats = $this->storageRepository->stats();
        return $this->render('storage/index.html.twig', ['stats' => $stats]);
    }

    #[Route('/storage/add', 'app_storage_add')]
    #[Route('/storage/{storageId}/edit', 'app_storage_edit', ['storageId' => '\d+'])]
    public function form(Request $request, EntityManager $em, Validate $validate, ?int $storageId): Response
    {
        $storage = new Storage();
        if ($storageId) {
            $storage = $this->storageRepository->find($storageId);
        }

        $form = $this->createForm(StorageType::class, $storage)->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($storage->getCapacity() == 0) {
                $storage->setCapacity(null);
            }

            if (!$storage->getId()) {
                $addOrUpdateMessage = 'ajouté';
                $result = $em->persist($storage);
            } else {
                $addOrUpdateMessage = 'modifié';
                $result = $em->flush();
            }

            if ($result['result']) {
                $this->addFlash('success', 'Rangement ' . $addOrUpdateMessage . ' avec succès.');
            }
            return $this->json($result);
        } elseif ($form->isSubmitted()) {
            return $this->json(['result' => false, 'messages' => $validate->getFormErrors($form)]);
        }

        $render = $this->render('storage/form.html.twig', ['form' => $form->createView(), 'storageId' => $storageId]);
        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route('/storage/{storageId}/delete', 'app_storage_delete', ['storageId' => '\d+'])]
    public function delete(EntityManager $em, int $storageId): Response
    {
        $storage = $this->storageRepository->find($storageId);
        if ($storage) {
            $result = $em->remove($storage, true);
            if ($result['result']) {
                $this->addFlash('success', 'Rangement supprimé avec succès.');
            }
            return $this->json($result);
        } else {
            return $this->json(['result' => false, 'message' => 'Le rangement est déjà supprimé']);
        }
    }

    #[Route('/storage/{storageId}/item/{itemQualityId}/remove', 'app_storage_remove', [
        'storageId' => '\d+', 'itemQualityId' => '\d+'
    ])]
    public function removeItem(
        ItemQualityRepository $itemQualityRepository,
        EntityManager $em,
        int $storageId,
        int $itemQualityId
    ): Response {
        $storage = $this->storageRepository->find($storageId);
        if ($storage) {
            $itemQuality = $itemQualityRepository->find($itemQualityId);
            if ($itemQuality) {
                $storage->removeItemQuality($itemQuality);
                if (
                    $storage->getCapacity() &&
                    $storage->isFull() &&
                    $storage->getItemQualities()->count() < $storage->getCapacity()
                ) {
                    $storage->setFull(false);
                }
                return $this->json($em->flush());
            } else {
                return $this->json(['result' => false, 'message' => 'L\'objet est introuvable']);
            }
        } else {
            return $this->json(['result' => false, 'message' => 'Le rangement est introuvable']);
        }
    }

    #[Route('/storage/{storageId}', 'app_storage_view', ['storageId' => '\d+'])]
    public function view(
        Request $request,
        PaginatorInterface $paginator,
        ItemQualityRepository $itemQualityRepository,
        int $storageId
    ): Response {
        $storage = $this->storageRepository->find($storageId);
        $query = $request->query;

        $filters = $query->all('filter');
        $filters = array_filter(
            $filters,
            function ($filter) {
                return !empty($filter) || $filter == 0;
            }
        );

        $itemQualities = $itemQualityRepository->findByFilter($filters, $storageId);
        $itemQualities = $paginator->paginate($itemQualities, $query->get('page', 1), $query->get('limit', 10));

        return $this->render('storage/view.html.twig', [
            'itemQualities' => $itemQualities,
            'storage' => $storage,
            'request' => $request
        ]);
    }

    #[Route('/storage/{storageId}/update', 'app_storage_update', ['storageId' => '\d+'])]
    public function update(Request $request, ItemQualityRepository $iqRepo, EntityManager $em, int $storageId): Response
    {
        $flush = false;
        $storage = $this->storageRepository->find($storageId);

        $datas = $request->request->all();
        foreach ($datas as $dataKey => $dataValue) {
            if ($dataKey === 'itemQuality' && $dataValue) {
                $itemQuality = $iqRepo->find($dataValue);
                if (!$storage->getItemQualities()->contains($itemQuality)) {
                    $storage->addItemQuality($itemQuality);
                    $flush = true;
                }
            } elseif ($dataKey === 'full') {
                $full = $dataValue == 'true';
                if ($full != $storage->isFull()) {
                    $storage->setFull($full);
                    $flush = true;
                }
            } elseif ($dataKey === 'capacity' && $dataValue != $storage->getCapacity()) {
                $storage->setCapacity((int) $dataValue);
                $flush = true;
            }
        }

        if ($flush) {
            if ($storage->getCapacity()) {
                if ($storage->getItemQualities()->count() == $storage->getCapacity()) {
                    $storage->setFull(true);
                } else {
                    $storage->setFull(false);
                }
            }

            $result = $em->flush();
            if (!$result['result']) {
                return $this->json($result);
            }
        }
        return $this->json(['result' => true]);
    }
}
