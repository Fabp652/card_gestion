<?php

namespace App\Form;

use App\Entity\ItemQuality;
use App\Entity\ItemSale;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemSaleType extends AbstractType
{
    private const LABEL_CLASS = 'form-label';
    private const ATTR_CLASS_CONTROL = 'form-control';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $data = $options['data'];
        $builder
            ->add('price', NumberType::class, [
                'label' => 'Prix',
                'scale' => 2,
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => ['class' => self::ATTR_CLASS_CONTROL],
                'required' => true
            ])
            ->add('link', UrlType::class, [
                'label' => 'Lien',
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => ['class' => self::ATTR_CLASS_CONTROL],
                'required' => false
            ])
            ->add('itemQualities', EntityType::class, [
                'class' => ItemQuality::class,
                'choice_value' => 'id',
                'choice_label' => 'choiceLabel',
                'label' => 'Objets',
                'attr' => ['class' => 'select2'],
                'multiple' => true,
                'by_reference' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('iq')
                        ->leftJoin('iq.item', 'i')
                    ;
                },
                'required' => true
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => ['class' => self::ATTR_CLASS_CONTROL],
                'required' => true
            ])
        ;

        if ($data->getId()) {
            $builder->add('sold', CheckboxType::class, [
                'label' => 'Vendu',
                'attr' => ['class' => 'form-check-input'],
                'row_attr' => ['class' => 'form-check form-switch']
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ItemSale::class
        ]);
    }
}
