<?php

namespace App\Controller;

use App\Entity\Purchase;
use App\Entity\Sale;
use App\Service\FileManager;
use App\Service\Validate;
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
        Validate $validate,
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
            $result = $fm->addOrReplace(
                $form->get('file')->getData(),
                strtolower($entityName),
                $entity->getName(),
                $entity->getFile()
            );

            if (!$result['false']) {
                return $this->json($result);
            }

            $fileManagerEntity = $result['newEntityFile'];
            $entity->setFile($fileManagerEntity);
            $result = $em->persist($fileManagerEntity, true);

            if ($result['result']) {
                $this->addFlash('success', 'Fichier ajouté avec succès.');
            }
            return $this->json($result);
        } elseif ($form->isSubmitted()) {
            return $this->json(['result' => false, 'messages' => $validate->getFormErrors($form)]);
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
