<?php

namespace App\Form;

use App\Entity\Item;
use App\Entity\ItemPurchase;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemPurchaseType extends AbstractType
{
    private const ATTR_CLASS_CONTROL = 'form-control';
    private const ATTR_CLASS_SELECT = 'form-select select2';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $itemId = $options['itemId'];
        $builder
            ->add('price', NumberType::class, [
                'scale' => 2,
                'attr' => [
                    'class' => self::ATTR_CLASS_CONTROL . ' linePrice'
                ]
            ])
            ->add('quantity', IntegerType::class, [
                'attr' => [
                    'class' => self::ATTR_CLASS_CONTROL,
                    'min' => 1,
                ]
            ])
            ->add('link', UrlType::class, [
                'attr' => ['class' => self::ATTR_CLASS_CONTROL],
                'required' => false
            ])
            ->add('item', EntityType::class, [
                'class' => Item::class,
                'choice_label' => 'choiceLabel',
                'choice_value' => 'id',
                'attr' => [
                    'class' => self::ATTR_CLASS_SELECT,
                    'data-width' => '100%'
                ],
                'query_builder' => function (EntityRepository $er) use ($itemId) {
                    $query = $er->createQueryBuilder('i')
                        ->leftJoin('i.collection', 'c')
                        ->orderBy('c.name')
                        ->addOrderBy('i.name')
                        ->setMaxResults('30')
                    ;
                    if ($itemId) {
                        $query->where('i.id = :id')
                            ->setParameter('id', $itemId)
                        ;
                    }
                },
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ItemPurchase::class,
            'itemId' => null
        ]);
    }
}
