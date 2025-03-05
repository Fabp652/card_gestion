<?php

namespace App\Form;

use App\Entity\Criteria;
use App\Entity\ItemQuality;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ItemQualityType extends AbstractType
{
    private const LABEL_CLASS = 'form-label';
    private const LABEL_CLASS_CHECKBOX = 'form-check-label';
    private const ATTR_CLASS_CONTROL = 'form-control rounded-0';
    private const ATTR_CLASS_CHECKBOX = 'form-check form-check-inline';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $category = $options['category'];
        $evaluate = $options['evaluate'];
        $data = $options['data'];
        $image = $options['image'];

        if (!$data->getId() || $evaluate) {
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
                    'required' => false
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
            ;
        }

        if (!$data->getId() || $image) {
            $builder->add(
                'file',
                FileType::class,
                [
                    'label' => 'Photo',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => [
                        'class' => self::ATTR_CLASS_CONTROL
                    ],
                    'mapped' => false,
                    'constraints' => [
                        new File([
                            'mimeTypes' => [
                                'image/jpeg',
                                'image/png'
                            ],
                            'mimeTypesMessage' => 'Seuls les image au formats jpeg ou png sont acceptés'
                        ])
                    ],
                    'required' => false
                ]
            );
        }
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
