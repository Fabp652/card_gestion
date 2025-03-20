<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Criteria;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CriteriaType extends AbstractType
{
    private const LABEL_CLASS = 'form-label';
    private const LABEL_CLASS_CHECKBOX = 'form-check-label';
    private const ATTR_CLASS_CONTROL = 'form-control';
    private const ATTR_CLASS_CHECKBOX = 'form-check form-check-inline';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => ['class' => self::ATTR_CLASS_CONTROL]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => ['class' => self::ATTR_CLASS_CONTROL],
                'required' => false
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_value' => 'id',
                'choice_label' => 'name',
                'label' => 'Catégories',
                'label_attr' => ['class' => self::LABEL_CLASS_CHECKBOX],
                'attr' => ['class' => self::ATTR_CLASS_CHECKBOX],
                'expanded' => true,
                'multiple' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC')
                    ;
                },
                'by_reference' => false
            ])
            ->add('point', IntegerType::class, [
                'label' => 'Point',
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => ['class' => self::ATTR_CLASS_CONTROL, 'max' => 10],
                'required' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Criteria::class
        ]);
    }
}
