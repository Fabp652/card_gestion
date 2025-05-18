<?php

namespace App\Controller;

use App\Entity\StorageType;
use App\Form\StorageTypeType;
use App\Repository\StorageTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StorageTypeController extends AbstractController
{
    public function __construct(private StorageTypeRepository $storageTypeRepository)
    {
    }

    #[Route('/storage/type', name: 'app_storage_type_add')]
    public function form(Request $request, EntityManagerInterface $em): Response
    {
        $storageType = new StorageType();

        $form = $this->createForm(StorageTypeType::class, $storageType)->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($storageType);
            $em->flush();

            return $this->json(['result' => true]);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $messages = [];
            foreach ($form->getErrors(true) as $error) {
                $field = $error->getOrigin()->getName();
                $messages[$field] = $error->getMessage();
            }
            return $this->json(['result' => false, 'messages' => $messages]);
        }

        $render = $this->render('storage_type/form.html.twig', [
            'form' => $form
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }
}
