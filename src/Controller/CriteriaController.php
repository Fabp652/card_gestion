<?php

namespace App\Controller;

use App\Entity\Criteria;
use App\Form\CriteriaType;
use App\Repository\CategoryRepository;
use App\Repository\CriteriaRepository;
use App\Service\EntityManager;
use App\Service\Validate;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CriteriaController extends AbstractController
{
    public function __construct(private CriteriaRepository $criteriaRepo,)
    {
    }

    #[Route('/criteria', 'app_criteria_list')]
    public function list(Request $request, PaginatorInterface $paginator, CategoryRepository $categoryRepo): Response
    {
        $filters = $request->query->all('filter');
        $filters = array_filter($filters);

        $criterias = $this->criteriaRepo->findByFilter($filters);
        $criterias = $paginator->paginate(
            $criterias,
            $request->query->get('page', 1),
            $request->query->get('limit', 10)
        );

        $categories = $categoryRepo->findAll();

        return $this->render('criteria/index.html.twig', [
            'criterias' => $criterias,
            'request' => $request,
            'categories' => $categories
        ]);
    }

    #[Route('/criteria/add', 'app_criteria_add')]
    #[Route('/criteria/{criteriaId}/edit', 'app_criteria_edit')]
    public function form(Request $request, EntityManager $em, Validate $validate, ?int $criteriaId): Response
    {
        $criteria = new Criteria();
        if ($criteriaId) {
            $criteria = $this->criteriaRepo->find($criteriaId);
        }

        $form = $this->createForm(CriteriaType::class, $criteria)->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $categories = $criteria->getCategories()->toArray();
            foreach ($categories as $category) {
                if ($criteria->getCategories()->contains($category->getParent())) {
                    $criteria->removeCategory($category);
                }
            }

            if (!$criteria->getId()) {
                $addOrUpdateMessage = 'ajoutée';
                $result = $em->persist($criteria, true);
            } else {
                $addOrUpdateMessage = 'modifiée';
                $result = $em->flush();
            }

            if ($result['result']) {
                $this->addFlash('success', 'Critère ' . $addOrUpdateMessage . ' avec succès.');
            }
            return $this->json($result);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            return $this->json(['result' => false, 'messages' => $validate]);
        }

        $render = $this->render('criteria/form.html.twig', [
            'form' => $form->createView(),
            'criteriaId' => $criteriaId
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route('/criteria/{criteriaId}/delete', 'app_criteria_delete', ['criteriaId' => '\d+'])]
    public function delete(EntityManager $em, int $criteriaId): Response
    {
        $item = $this->criteriaRepo->find($criteriaId);
        if ($item) {
            $result = $em->remove($item, true);
            return $this->json($result);
        } else {
            return $this->json(['result' => false, 'message' => 'Le critère est déjà supprimé']);
        }
    }
}
