<?php

namespace App\Form;

use App\Entity\Criteria;
use App\Entity\ItemQuality;
use App\Entity\Storage;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class ItemQualityType extends AbstractType
{
    private const LABEL_CLASS = 'form-label';
    private const LABEL_CLASS_CHECKBOX = 'form-check-label';
    private const ATTR_CLASS_CONTROL = 'form-control';
    private const ATTR_CLASS_CHECKBOX = 'form-check form-check-inline';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $category = $options['category'];
        $data = $options['data'];
        if ($data->getQuality() == 10 && $data->getCriterias()->isEmpty()) {
            $disabled = true;
        } else {
            $disabled = false;
        }

        $builder
            ->add('criterias', EntityType::class, [
                'class' => Criteria::class,
                'choice_value' => 'id',
                'choice_label' => 'name',
                'label' => 'Critères',
                'label_attr' => ['class' => self::LABEL_CLASS_CHECKBOX],
                'attr' => ['class' => self::ATTR_CLASS_CHECKBOX],
                'expanded' => true,
                'multiple' => true,
                'by_reference' => false,
                'query_builder' => function (EntityRepository $er) use ($category) {
                    return $er->createQueryBuilder('c')
                        ->join('c.categories', 'cat', Join::WITH, 'cat.id = :category OR cat.id = :parent')
                        ->setParameter('category', $category->getId())
                        ->setParameter('parent', $category->getParent())
                    ;
                },
                'choice_attr' => function ($choice, string $key, mixed $value) {
                    return ['data-point' => $choice->getPoint()];
                },
                'required' => false,
                'disabled' => $disabled
            ])
            ->add(
                'quality',
                IntegerType::class,
                [
                    'label' => 'Qualité',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => [
                        'class' => self::ATTR_CLASS_CONTROL,
                        'min' => 0,
                        'max' => 10
                    ],
                    'data' => $data->getQuality() ?? 10,
                    'required' => false
                ]
            )
            ->add(
                'files',
                FileType::class,
                [
                    'label' => 'Photo',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => [
                        'class' => self::ATTR_CLASS_CONTROL . ' fileInput'
                    ],
                    'mapped' => false,
                    'constraints' => [
                        new All([
                            'constraints' => new File([
                                'mimeTypes' => [
                                    'image/jpeg',
                                    'image/png'
                                ],
                                'mimeTypesMessage' => 'Seuls les image au formats jpeg ou png sont acceptés'
                            ])
                        ])
                    ],
                    'required' => false,
                    'multiple' => true
                ]
            )
            ->add('storage', EntityType::class, [
                'class' => Storage::class,
                'label' => 'Rangement',
                'choice_value' => 'id',
                'choice_label' => 'name',
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => ['class' => 'form-select'],
                'query_builder' => function (EntityRepository $er): QueryBuilder {
                    return $er->createQueryBuilder('s')
                        ->orderBy('s.name', 'ASC')
                        ->where('s.full = false')
                    ;
                },
                'required' => false,
                'by_reference' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ItemQuality::class,
            'category' => null,
            'evaluate' => false,
            'image' => false
        ]);
    }
}
