<?php

namespace App\Form;

use App\Entity\Market;
use App\Entity\Sale;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SaleType extends AbstractType
{
    private const LABEL_CLASS = 'form-label';
    private const ATTR_CLASS_CONTROL = 'form-control';
    private const ATTR_CLASS_CHECK = 'form-check-input';
    private const ATTR_CLASS_CHECK_ROW = 'form-check form-switch';
    private const ATTR_CLASS_CHECK_LABEL = 'form-check-label fw-medium';
    private const ATTR_CLASS_SELECT = 'form-select select2';

    public function buildForm(FormBuilderInterface $builder, array $options): void
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
            ->add('market', EntityType::class, [
                'class' => Market::class,
                'label' => 'Vendu à',
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => [
                    'class' => self::ATTR_CLASS_SELECT,
                    'data-width' => '100%',
                    'data-ajax--url' => $options['marketUrl']
                ],
                'choice_label' => 'name',
                'choice_value' => 'id'
            ])
        ;

        $data = $options['data'];
        if ($data->getId()) {
            $builder->add('link', UrlType::class, [
                'label' => 'Lien',
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => ['class' => self::ATTR_CLASS_CONTROL],
                'required' => false
            ]);

            if (!$data->isOrder()) {
                $builder->add('soldAt', DateType::class, [
                    'label' => 'Vendu le',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS_CONTROL . ' datepicker'],
                    'html5' => false,
                    'required' => false
                ]);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sale::class,
            'marketUrl' => null
        ]);
    }
}
