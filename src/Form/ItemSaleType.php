<?php

namespace App\Form;

use App\Entity\ItemQuality;
use App\Entity\ItemSale;
use Doctrine\ORM\EntityRepository;
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
        $itemQualityId = $options['itemQualityId'];

        $builder
            ->add('price', NumberType::class, [
                'label' => 'Prix',
                'scale' => 2,
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => ['class' => self::ATTR_CLASS_CONTROL],
            ])
            ->add('itemQuality', EntityType::class, [
                'class' => ItemQuality::class,
                'choice_value' => 'id',
                'choice_label' => 'choiceLabel',
                'label' => 'Objets',
                'attr' => [
                    'class' => self::ATTR_CLASS_CONTROL . ' select2',
                    'data-width' => '100%'
                ],
                'label_attr' => ['class' => self::LABEL_CLASS],
                'required' => false,
                'query_builder' => function (EntityRepository $er) use ($itemQualityId) {
                    return $er->createQueryBuilder('iq')
                        ->where('iq.id = :id')
                        ->setParameter('id', $itemQualityId)
                    ;
                }
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ItemSale::class,
            'itemQualityId' => null
        ]);
    }
}
