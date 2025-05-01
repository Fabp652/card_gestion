<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
#[UniqueEntity(fields: ['name', 'parent', 'reference', 'category', 'collection'])]
class Item
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'L\'objet doit avoir un nom')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom doit avoir au maximum 100 caractères')]
    private ?string $name = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Assert\Length(
        max: 45,
        min: 1,
        maxMessage: 'Le nom doit avoir au maximum 45 caractères',
        minMessage: 'Le nom doit avoir au minimum 1 caractère'
    )]
    private ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'childs')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $childs;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[Assert\NotBlank(message: 'L\'objet doit avoir une catégorie')]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    private ?Rarity $rarity = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[Assert\NotBlank(message: 'L\'objet doit appartenir à une collection')]
    private ?Collections $collection = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'La quantité doit être supérieur ou égal à 0')]
    private ?int $number = 0;

    #[ORM\Column]
    #[Assert\Positive(message: 'La prix doit être supérieur à 0')]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: 'L\'URL du lien n\'est pas valide')]
    private ?string $link = null;

    #[ORM\OneToMany(targetEntity: ItemQuality::class, mappedBy: 'item', cascade: ['remove'])]
    private Collection $itemQualities;

    /**
     * @var Collection<int, ItemPurchase>
     */
    #[ORM\OneToMany(targetEntity: ItemPurchase::class, mappedBy: 'item')]
    private Collection $itemPurchases;

    public function __construct()
    {
        $this->itemQualities = new ArrayCollection();
        $this->itemPurchases = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChilds(): Collection
    {
        return $this->childs;
    }

    public function addChild(self $child): static
    {
        if (!$this->childs->contains($child)) {
            $this->childs->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->childs->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getRarity(): ?Rarity
    {
        return $this->rarity;
    }

    public function setRarity(?Rarity $rarity): static
    {
        $this->rarity = $rarity;

        return $this;
    }

    public function getCollection(): ?Collections
    {
        return $this->collection;
    }

    public function setCollection(?Collections $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(string $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, ItemQuality>
     */
    public function getItemQualities(): Collection
    {
        return $this->itemQualities;
    }

    public function addItemQuality(ItemQuality $itemQuality): static
    {
        if (!$this->itemQualities->contains($itemQuality)) {
            $this->itemQualities->add($itemQuality);
            $itemQuality->setItem($this);
        }

        return $this;
    }

    public function removeItemQuality(ItemQuality $itemQuality): static
    {
        if ($this->itemQualities->removeElement($itemQuality)) {
            // set the owning side to null (unless already changed)
            if ($itemQuality->getItem() === $this) {
                $itemQuality->setItem(null);
            }
        }

        return $this;
    }

    public function getQualityAverage(): int|null
    {
        $totalQuality = 0;
        $totalEvaluated = 0;
        foreach ($this->itemQualities as $itemQuality) {
            if (is_int($itemQuality->getQuality())) {
                $totalQuality += $itemQuality->getQuality();
                $totalEvaluated++;
            }
        }

        if ($totalEvaluated == 0) {
            return null;
        } elseif ($totalQuality == 0) {
            return $totalQuality;
        }
        return $totalQuality / $totalEvaluated;
    }

    public function getNotEvaluatedNumber(): int
    {
        $notEvaluated = 0;
        if ($this->itemQualities->isEmpty()) {
            return $this->number;
        } elseif ($this->itemQualities->count() < $this->number) {
            $notEvaluated = $this->number - $this->itemQualities->count();
        }

        $itemQualitiesNotEvaluated = $this->itemQualities->filter(function ($itemQuality) {
            return is_null($itemQuality->getQuality());
        });

        return $notEvaluated + $itemQualitiesNotEvaluated->count();
    }

    /**
     * @return Collection<int, ItemPurchase>
     */
    public function getItemPurchases(): Collection
    {
        return $this->itemPurchases;
    }

    public function addItemPurchase(ItemPurchase $itemPurchase): static
    {
        if (!$this->itemPurchases->contains($itemPurchase)) {
            $this->itemPurchases->add($itemPurchase);
            $itemPurchase->setItem($this);
        }

        return $this;
    }

    public function removeItemPurchase(ItemPurchase $itemPurchase): static
    {
        if ($this->itemPurchases->removeElement($itemPurchase)) {
            // set the owning side to null (unless already changed)
            if ($itemPurchase->getItem() === $this) {
                $itemPurchase->setItem(null);
            }
        }

        return $this;
    }
}
