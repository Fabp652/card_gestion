<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Collections;
use App\Service\FileManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\File as FileFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CollectionType extends AbstractType
{
    private const LABEL_CLASS = 'form-label';
    private const ATTR_CLASS_CONTROL = 'form-control';
    private const ATTR_CLASS_SELECT = 'form-select';

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
                    'label' => 'Catégorie',
                    'choice_value' => 'id',
                    'choice_label' => 'name',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => [
                        'class' => self::ATTR_CLASS_SELECT . ' select2',
                        'data-tags' => 'true',
                        'data-width' => '100%'
                    ],
                    'required' => false
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Collections::class
        ]);
    }
}
