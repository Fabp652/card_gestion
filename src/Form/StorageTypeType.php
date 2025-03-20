<?php

namespace App\Form;

use App\Entity\StorageType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StorageTypeType extends AbstractType
{
    private const LABEL_CLASS = 'form-label';
    private const ATTR_CLASS_CONTROL = 'form-control';

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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StorageType::class
        ]);
    }
}
