<?php

namespace App\Controller;

use App\Entity\Storage;
use App\Form\StorageType;
use App\Repository\ItemRepository;
use App\Repository\StorageRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    public function delete(Request $request, int $storageId): Response
    {
        $referer = $request->headers->get('referer');

        $storage = $this->storageRepository->find($storageId);
        if ($storage) {
            $this->em->remove($storage);
            $this->em->flush();
            $this->addFlash('success', "L'objet est supprimé");
        } else {
            $this->addFlash('warning', "L'objet est déjà supprimer");
        }

        return $this->redirect($referer);
    }

    #[Route(
        '/storage/{storageId}/item/{itemId}/add',
        name: 'app_storage_item_add',
        requirements: ['storageId' => '\d+', 'itemId' => '\d+']
    )]
    public function addItem(
        Request $request,
        ItemRepository $itemRepository,
        int $storageId,
        int $itemId
    ): Response {
        $referer = $request->headers->get('referer');

        $storage = $this->storageRepository->find($storageId);
        if ($storage) {
            $item = $itemRepository->find($itemId);
            if ($item) {
                $storage->addItem($item);
                $this->em->flush();
            } else {
                $this->addFlash('danger', 'Objet non trouvé');
            }
        } else {
            $this->addFlash('danger', 'Rangement non trouvé');
        }

        return $this->redirect($referer);
    }

    #[Route(
        '/storage/{storageId}/item/{itemId}/remove',
        name: 'app_storage_remove',
        requirements: ['storageId' => '\d+', 'itemId' => '\d+']
    )]
    public function removeItem(
        Request $request,
        ItemRepository $itemRepository,
        int $storageId,
        int $itemId
    ): Response {
        $referer = $request->headers->get('referer');

        $storage = $this->storageRepository->find($storageId);
        if ($storage) {
            $item = $itemRepository->find($itemId);
            if ($item) {
                $storage->removeItem($item);
                $this->em->flush();
            } else {
                $this->addFlash('danger', 'Objet non trouvé');
            }
        } else {
            $this->addFlash('danger', 'Rangement non trouvé');
        }

        return $this->redirect($referer);
    }

    #[Route(
        '/storage/{storageId}/full',
        name: 'app_storage_full',
        requirements: ['storageId' => '\d+']
    )]
    public function full(Request $request, int $storageId): Response
    {
        if ($request->request->has('full')) {
            $storage = $this->storageRepository->find($storageId);
            $full = $request->get('full') == 'true' ? true : false;

            $storage->setFull($full);
            $this->em->flush();

            return $this->json(['result' => true]);
        } else {
            return $this->json(['result' => false, 'message' => 'Une erreur est survenue']);
        }
    }
}
