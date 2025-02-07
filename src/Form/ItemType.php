<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Collections;
use App\Entity\Item;
use App\Entity\Rarity;
use App\Repository\CategoryRepository;
use App\Repository\CollectionsRepository;
use App\Repository\RarityRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
    private const ATTR_CLASS = 'form-control';

    public function __construct(
        private RarityRepository $rarityRepo,
        private CategoryRepository $categoryRepo,
        private CollectionsRepository $collectionRepo
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $collection = $options['collection'];

        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => 'Nom',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                    'required' => true
                ]
            )
            ->add(
                'reference',
                TextType::class,
                [
                    'label' => 'Référence',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                    'required' => false
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
                    'required' => true
                ]
            )
            ->add(
                'quality',
                IntegerType::class,
                [
                    'label' => 'Qualité',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                    'required' => true
                ]
            )
            ->add(
                'number',
                IntegerType::class,
                [
                    'label' => 'Nombre',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                    'required' => true
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
            )
            ->add(
                'category',
                EntityType::class,
                [
                    'class' => Category::class,
                    'label' => 'Catégorie',
                    'choice_value' => 'id',
                    'choice_label' => 'name',
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                    'query_builder' => function (EntityRepository $er) use ($collection): QueryBuilder {
                        return $er->createQueryBuilder('c')
                            ->orderBy('c.name', 'ASC')
                            ->where('c.parent = :parent')
                            ->setParameter('parent', $collection->getCategory())
                        ;
                    }
                ]
            )
        ;

        if ($collection->getRarities()->count() > 0) {
            $builder->add(
                'rarity',
                EntityType::class,
                [
                    'class' => Rarity::class,
                    'choice_label' => 'name',
                    'choice_value' => 'id',
                    'query_builder' => function (EntityRepository $er) use ($collection): QueryBuilder {
                        return $er->createQueryBuilder('r')
                            ->orderBy('r.grade', 'ASC')
                            ->where('r.collection = :collection')
                            ->setParameter('collection', $collection->getId())
                        ;
                    },
                    'label_attr' => ['class' => self::LABEL_CLASS],
                    'attr' => ['class' => self::ATTR_CLASS],
                    'label' => 'Rareté',
                    'required' => false
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Item::class,
            'collection' => null
        ]);
    }
}
