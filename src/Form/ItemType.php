<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Collections;
use App\Entity\Item;
use App\Entity\Rarity;
use App\Repository\CategoryRepository;
use App\Repository\CollectionsRepository;
use App\Repository\RarityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemType extends AbstractType
{
    private const LABEL_CLASS = 'form-label';
    private const ATTR_CLASS = 'form-control mb-3';

    public function __construct(
        private RarityRepository $rarityRepo,
        private CategoryRepository $categoryRepo,
        private CollectionsRepository $collectionRepo
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $rarities = $this->rarityRepo->findAll();
        $categories = $this->categoryRepo->findAll();
        $collections = $this->collectionRepo->findAll();

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
                        return $rarity ? ['class' => 'rarity_' . strtolower($rarity->getName())] : [];
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
                'quality',
                IntegerType::class,
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
                'link',
                UrlType::class,
                [
                    'label' => 'Lien',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                ]
            )
            ->add(
                'category',
                ChoiceType::class,
                [
                    'label' => 'Category',
                    'choices' => $categories,
                    'choice_value' => 'id',
                    'choice_label' => function (?Category $category): string {
                        return $category ? $category->getName() : '';
                    },
                    'choice_attr' => function (?Category $category): array {
                        return $category ? ['class' => 'rarity_' . strtolower($category->getName())] : [];
                    },
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                ]
            )
            ->add(
                'collection',
                ChoiceType::class,
                [
                    'label' => 'Collection',
                    'choices' => $collections,
                    'choice_value' => 'id',
                    'choice_label' => function (?Collections $collection): string {
                        return $collection ? $collection->getName() : '';
                    },
                    'choice_attr' => function (?Collections $collection): array {
                        return $collection ? ['class' => 'rarity_' . strtolower($collection->getName())] : [];
                    },
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Item::class,
        ]);
    }
}
