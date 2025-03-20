<?php

namespace App\Form;

use App\Entity\Storage;
use App\Entity\StorageType as EntityStorageType;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StorageType extends AbstractType
{
    private const LABEL_CLASS = 'form-label';
    private const ATTR_CLASS_CONTROL = 'form-control';
    private const ATTR_CLASS_SELECT = 'form-select';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => 'Nom',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS_CONTROL],
                    'required' => true
                ]
            )
            ->add('capacity', IntegerType::class, [
                'label' => 'Capicité de rangement',
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => ['class' => self::ATTR_CLASS_CONTROL],
                'required' => false
            ])
            ->add('storageType', EntityType::class, [
                'class' => EntityStorageType::class,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'query_builder' => function (EntityRepository $er): QueryBuilder {
                    return $er->createQueryBuilder('st');
                },
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => ['class' => self::ATTR_CLASS_SELECT],
                'label' => 'Type de rangement',
                'required' => true
            ])
        ;

        $storage = $options['data'];
        if ($storage->getId()) {
            $builder->add('full', CheckboxType::class, [
                'label' => 'Plein',
                'attr' => ['class' => 'form-check-input'],
                'row_attr' => ['class' => 'form-check form-switch']
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Storage::class
        ]);
    }
}