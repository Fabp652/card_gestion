<?php

namespace App\Form;

use App\Entity\Game;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GameType extends AbstractType
{
    private const LABEL_CLASS = 'form-label';
    private const ATTR_CLASS = 'form-control mb-3';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => 'Nom',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                ]
            )
            ->add(
                'price', 
                NumberType::class, 
                [
                    'label' => 'Prix',
                    'scale' => 2,
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                ]
            )
            ->add(
                'date',
                DateType::class,
                [
                    'label' => 'Date',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                    'widget' => 'single_text',
                    
                ]
            )
            ->add(
                'quality',
                TextType::class,
                [
                    'label' => 'Qualité',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                ]
            )
            ->add(
                'number',
                IntegerType::class,
                [
                    'label' => 'Nombre',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                ]
            )
            ->add(
                'console',
                TextType::class,
                [
                    'label' => 'Console',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                ]
            )
            ->add(
                'link',
                UrlType::class,
                [
                    'label' => 'Lien',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                    'required' => false
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Game::class,
        ]);
    }
}
