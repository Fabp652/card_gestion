<?php

namespace App\Controller;

use App\Entity\StorageType;
use App\Form\StorageTypeType;
use App\Repository\StorageTypeRepository;
use App\Service\EntityManager;
use App\Service\Validate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StorageTypeController extends AbstractController
{
    public function __construct(private StorageTypeRepository $storageTypeRepository)
    {
    }

    #[Route('/storage/type', 'app_storage_type_add')]
    public function form(Request $request, EntityManager $em, Validate $validate): Response
    {
        $storageType = new StorageType();
        $form = $this->createForm(StorageTypeType::class, $storageType)->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $result = $em->persist($storageType, true);
            if ($result['result']) {
                $this->addFlash('success', 'Type de rangement ajouté avec succès.');
            }
            return $this->json($result);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            return $this->json(['result' => false, 'messages' => $validate->getFormErrors($form)]);
        }

        $render = $this->render('storage_type/form.html.twig', ['form' => $form]);
        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }
}
