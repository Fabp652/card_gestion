<?php

namespace App\Controller;

use App\Entity\Storage;
use App\Form\StorageType;
use App\Repository\ItemQualityRepository;
use App\Repository\StorageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StorageController extends AbstractController
{
    public function __construct(private StorageRepository $storageRepository, private EntityManagerInterface $em)
    {
    }

    #[Route('/storage', name: 'app_storage_list')]
    public function list(): Response
    {
        $stats = $this->storageRepository->stats();

        return $this->render('storage/index.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/storage/add', name: 'app_storage_add')]
    #[Route(
        '/storage/{storageId}/edit',
        name: 'app_storage_edit',
        requirements: ['storageId' => '\d+']
    )]
    public function form(Request $request, ?int $storageId): Response
    {
        if ($storageId) {
            $storage = $this->storageRepository->find($storageId);
        } else {
            $storage = new Storage();
        }

        $form = $this->createForm(StorageType::class, $storage)->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($storage->getCapacity() == 0) {
                $storage->setCapacity(null);
            }
            $this->em->persist($storage);
            $this->em->flush();

            return $this->json(['result' => true]);
        }

        $render = $this->render('storage/form.html.twig', [
            'form' => $form->createView(),
            'storageId' => $storageId
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route('/storage/{storageId}/delete', name: 'app_storage_delete', requirements: ['storageId' => '\d+'])]
    public function delete(int $storageId): Response
    {
        $storage = $this->storageRepository->find($storageId);
        if ($storage) {
            $this->em->remove($storage);
            $this->em->flush();

            return $this->json(['result' => true]);
        } else {
            return $this->json(['result' => false, 'message' => 'Le rangement est déjà supprimé']);
        }
    }

    #[Route(
        '/storage/{storageId}/item/{itemQualityId}/remove',
        name: 'app_storage_remove',
        requirements: ['storageId' => '\d+', 'itemQualityId' => '\d+']
    )]
    public function removeItem(
        ItemQualityRepository $itemQualityRepository,
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
                $this->em->flush();

                return $this->json(['result' => true]);
            } else {
                return $this->json(['result' => false, 'message' => 'L\'objet est introuvable']);
            }
        } else {
            return $this->json(['result' => false, 'message' => 'Le rangement est introuvable']);
        }
    }

    #[Route(
        '/storage/{storageId}',
        name: 'app_storage_view',
        requirements: ['storageId' => '\d+']
    )]
    public function view(
        Request $request,
        PaginatorInterface $paginator,
        ItemQualityRepository $itemQualityRepository,
        int $storageId
    ): Response {
        $storage = $this->storageRepository->find($storageId);

        $filters = $request->query->all('filter');
        $filters = array_filter(
            $filters,
            function ($filter) {
                return !empty($filter) || $filter == 0;
            }
        );

        $itemQualities = $itemQualityRepository->findByFilter($filters, $storageId);

        $itemQualities = $paginator->paginate(
            $itemQualities,
            $request->query->get('page', 1),
            $request->query->get('limit', 10)
        );

        return $this->render('storage/view.html.twig', [
            'itemQualities' => $itemQualities,
            'storage' => $storage,
            'request' => $request
        ]);
    }

    #[Route(
        '/storage/{storageId}/update',
        name: 'app_storage_update',
        requirements: ['storageId' => '\d+']
    )]
    public function update(Request $request, ItemQualityRepository $itemQualityRepository, int $storageId): Response
    {
        $flush = false;
        $storage = $this->storageRepository->find($storageId);

        $datas = $request->request->all();
        foreach ($datas as $dataKey => $dataValue) {
            if ($dataKey === 'itemQuality' && $dataValue) {
                $itemQuality = $itemQualityRepository->find($dataValue);
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
            $this->em->flush();
        }

        return $this->json(['result' => true]);
    }
}
