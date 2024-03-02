<?php

namespace App\Form;

use App\Entity\Card;
use App\Entity\Rarity;
use App\Repository\RarityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardType extends AbstractType
{
    private const LABEL_CLASS = 'form-label';
    private const ATTR_CLASS = 'form-control mb-3';

    /** @var RarityRepository $rarittyRepo */ 
    private RarityRepository $rarityRepo;

    public function __construct(RarityRepository $rarityRepo)
    {
        $this->rarityRepo = $rarityRepo;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $rarities = $this->rarityRepo->findAll();

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
                'reference', 
                TextType::class, 
                [
                    'label' => 'Référence',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                ]
            )
            ->add(
                'rarity',
                ChoiceType::class,
                [
                    'label' => 'Rareté',
                    'choices' => $rarities,
                    'choice_value' => 'id',
                    'choice_label' => function (?Rarity $rarity): string {
                        return $rarity ? $rarity->getName() : '';
                    },
                    'choice_attr' => function (?Rarity $rarity): array {
                        return $rarity ? ['class' => 'rarity_'.strtolower($rarity->getName())]: [];
                    },
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
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Card::class,
        ]);
    }
}
