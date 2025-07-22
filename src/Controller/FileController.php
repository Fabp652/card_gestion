<?php

namespace App\Controller;

use App\Entity\Purchase;
use App\Entity\Sale;
use App\Service\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\File;

final class FileController extends AbstractController
{
    private const ENTITY_NAMESPACE = 'App\Entity\\';

    #[Route(
        '/file/upload/{entityName}/{entityId}',
        'app_file_upload',
        ['entityId' => '\d+']
    )]
    public function upload(
        Request $request,
        EntityManagerInterface $em,
        FileManager $fm,
        string $entityName,
        int $entityId
    ): Response {
        $form = $this->createFormBuilder()
            ->add('file', FileType::class, [
                'label' => 'Fichier',
                'label_attr' => ['class' => 'form-label'],
                'attr' => [
                    'class' => 'form-control fileInput'
                ],
                'constraints' => new File([
                    'mimeTypes' => [
                        'image/jpeg',
                        'application/pdf'
                    ],
                    'mimeTypesMessage' => 'Seuls les fichiers au formats jpeg ou pdf sont acceptés'
                ])
            ])
            ->getForm()
            ->handleRequest($request)
        ;
        /** @var Sale|Purchase $entity */
        $entity = $em->getRepository(self::ENTITY_NAMESPACE . ucfirst($entityName))->find($entityId);
        $file = $entity->getFile();
        if ($form->isSubmitted() && $form->isValid()) {
            if ($file) {
                $result = $fm->removeFile($file->getName(), $file->getFolder());
                if (!$result) {
                    return $this->json([
                        'result' => false,
                        'message' => 'Une erreur est survenue lors de l\'ajout du fichier.'
                    ]);
                }

                $em->remove($file);
            }

            $fileManagerEntity = $fm->upload(
                strtolower($entityName),
                $entity->getName(),
                $form->get('file')->getData()
            );

            if (!$fileManagerEntity) {
                return $this->json([
                    'result' => false,
                    'message' => 'Une erreur est survenue lors de l\'ajout du fichier.'
                ]);
            }

            $em->persist($fileManagerEntity);
            $entity->setFile($fileManagerEntity);

            $em->flush();

            return $this->json(['result' => true]);
        } elseif ($form->isSubmitted()) {
            $messages = [];
            foreach ($form->getErrors(true) as $error) {
                $field = $error->getOrigin()->getName();
                $messages[$field] = $error->getMessage();
            }
            return $this->json(['result' => false, 'messages' => $messages]);
        }

        $render = $this->render('file/upload.html.twig', [
            'form' => $form,
            'file' => $file,
            'entityName' => $entityName,
            'entityId' => $entityId
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }
}
