<?php

namespace App\Form;

use App\Entity\Purchase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PurchaseType extends AbstractType
{
    private const LABEL_CLASS = 'form-label';
    private const ATTR_CLASS_CONTROL = 'form-control';
    private const ATTR_CLASS_CHECK = 'form-check-input';
    private const ATTR_CLASS_CHECK_ROW = 'form-check form-switch';
    private const ATTR_CLASS_CHECK_LABEL = 'form-check-label fw-medium';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => ['class' => self::ATTR_CLASS_CONTROL]
            ])
            ->add('isOrder', CheckboxType::class, [
                'label' => 'Commande',
                'attr' => ['class' => self::ATTR_CLASS_CHECK],
                'row_attr' => ['class' => self::ATTR_CLASS_CHECK_ROW],
                'label_attr' => ['class' => self::ATTR_CLASS_CHECK_LABEL],
                'required' => false
            ])
            ->add(
                'link',
                UrlType::class,
                [
                    'label' => 'Lien',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS_CONTROL],
                    'required' => false
                ]
            )
        ;

        $purchase = $options['data'];
        if ($purchase->getId()) {
            $builder
                ->add('buyAt', DateType::class, [
                    'label' => 'Payé le',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS_CONTROL . ' datepicker'],
                    'html5' => false,
                    'required' => false
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Purchase::class
        ]);
    }
}
