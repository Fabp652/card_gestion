<?php

namespace App\Entity;

use App\Repository\ItemQualityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ItemQualityRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: false)]
class ItemQuality
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: 'La note de qualité doit être supérieur ou égal à 0')]
    private ?int $quality = null;

    #[ORM\ManyToOne(inversedBy: 'itemQualities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Item $item = null;

    #[ORM\ManyToMany(targetEntity: Criteria::class, inversedBy: 'itemQualities')]
    private Collection $criterias;

    #[ORM\ManyToOne]
    private ?FileManager $file = null;

    #[ORM\ManyToOne(inversedBy: 'itemQualities')]
    private ?Storage $storage = null;

    #[ORM\Column]
    private ?int $sort = null;

    /**
     * @var Collection<int, ItemSale>
     */
    #[ORM\OneToMany(targetEntity: ItemSale::class, mappedBy: 'itemQuality')]
    private ?Collection $itemSales = null;

    #[ORM\Column(options:['default' => true])]
    private ?bool $availableSale = true;

    public function __construct()
    {
        $this->criterias = new ArrayCollection();
        $this->itemSales = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuality(): ?int
    {
        return $this->quality;
    }

    public function setQuality(?int $quality): static
    {
        $this->quality = $quality;

        return $this;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): static
    {
        $this->item = $item;

        return $this;
    }

    /**
     * @return Collection<int, Criteria>
     */
    public function getCriterias(): Collection
    {
        return $this->criterias;
    }

    public function addCriteria(Criteria $criteria): static
    {
        if (!$this->criterias->contains($criteria)) {
            $this->criterias->add($criteria);
        }

        return $this;
    }

    public function removeCriteria(Criteria $criteria): static
    {
        $this->criterias->removeElement($criteria);

        return $this;
    }

    public function getFile(): ?FileManager
    {
        return $this->file;
    }

    public function setFile(?FileManager $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function getStorage(): ?Storage
    {
        return $this->storage;
    }

    public function setStorage(?Storage $storage): static
    {
        $this->storage = $storage;

        return $this;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function getChoiceLabel(): string
    {
        $choiceLabel = 'N°' . $this->sort;
        if ($this->item->getReference()) {
            $choiceLabel .= ' - ' . $this->item->getReference();
        }
        $choiceLabel .= ' - ' . $this->item->getName() . ' (' . $this->item->getCollection()->getName() . ')';

        return $choiceLabel;
    }

    /**
     * @return Collection<int, ItemSale>
     */
    public function getItemSales(): Collection
    {
        return $this->itemSales;
    }

    public function addItemSale(ItemSale $itemSale): static
    {
        if (!$this->itemSales->contains($itemSale)) {
            $this->itemSales->add($itemSale);
            $itemSale->setItemQuality($this);
        }

        return $this;
    }

    public function removeItemSale(ItemSale $itemSale): static
    {
        if ($this->itemSales->removeElement($itemSale)) {
            // set the owning side to null (unless already changed)
            if ($itemSale->getSale() === $this) {
                $itemSale->setSale(null);
            }
        }

        return $this;
    }

    public function getItemSale(): ?ItemSale
    {
        return $this->itemSales->first();
    }

    public function isAvailableSale(): ?bool
    {
        return $this->availableSale;
    }

    public function setAvailableSale(bool $availableSale): static
    {
        $this->availableSale = $availableSale;

        return $this;
    }
}
