<?php

namespace App\Form;

use App\Entity\Rarity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RarityType extends AbstractType
{
    private const LABEL_CLASS = 'form-label';
    private const ATTR_CLASS_CONTROL = 'form-control';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $gradeHelp = 'Détermine le niveau de rareté à partir de 0';
        $gradeHelp .= ', donc plus le niveau est grand plus la rareté est rare';

        $gradeLabel = 'Niveau de rareté <span data-bs-toggle="tooltip" data-bs-placement="top" ';
        $gradeLabel .= ' data-bs-title="' . $gradeHelp . '" class="modalTooltip"><i class="fa-solid fa-circle-question fa-sm"></i></span>';

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => ['class' => self::ATTR_CLASS_CONTROL]
            ])
            ->add('grade', IntegerType::class, [
                'label' => $gradeLabel,
                'label_html' => true,
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => [
                    'class' => self::ATTR_CLASS_CONTROL,
                    'min' => 0,
                ]
            ])
            ->add('file', FileType::class, [
                'label' => 'Icône',
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => ['class' => self::ATTR_CLASS_CONTROL],
                'required' => false,
                'mapped' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Rarity::class
        ]);
    }
}
