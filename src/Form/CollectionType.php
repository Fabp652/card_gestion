<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Collections;
use App\Form\DataTransformer\Model\CategoryTransformer as ModelCategoryTransformer;
use App\Form\DataTransformer\View\CategoryTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CollectionType extends AbstractType
{
    private const LABEL_CLASS = 'form-label';
    private const ATTR_CLASS_CONTROL = 'form-control';
    private const ATTR_CLASS_SELECT = 'form-select select2';

    public function __construct(
        private CategoryTransformer $Viewtransformer,
        private ModelCategoryTransformer $ModelTransformer,
        private EntityManagerInterface $em
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => ['class' => self::ATTR_CLASS_CONTROL]
            ])
            ->add(
                'category',
                EntityType::class,
                [
                    'class' => Category::class,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('c')
                            ->where('c.parent IS NULL')
                        ;
                    },
                    'choice_label' => 'name',
                    'choice_value' => 'id',
                    'label' => 'Catégorie',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => [
                        'class' => self::ATTR_CLASS_SELECT,
                        'data-tags' => 'true',
                        'data-width' => '100%'
                    ],
                    'required' => true
                ]
            )
            ->add(
                'file',
                FileType::class,
                [
                    'label' => 'Logo',
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
            )
        ;

        $builder->get('category')
            ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Collections::class
        ]);
    }

    public function onPreSubmit(PreSubmitEvent $event): void
    {
        $data = $event->getData();
        if ($data && !is_numeric($data)) {
            $category = new Category();
            $category->setName($data);

            $event->getForm()->getParent()->add(
                'category',
                EntityType::class,
                [
                    'class' => Category::class,
                    'query_builder' => null,
                    'choice_label' => 'name',
                    'choice_value' => 'name',
                    'label' => 'Catégorie',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => [
                        'class' => self::ATTR_CLASS_SELECT,
                        'data-tags' => 'true',
                        'data-width' => '100%'
                    ],
                    'required' => true,
                    'choices' => [$category->getName() => $category],
                    'data' => $data,
                    'by_reference' => false,
                ]
            );
        }
    }
}
