<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity(repositoryClass: ItemRepository::class), HasLifecycleCallbacks]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'childs')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $childs;

    #[ORM\ManyToOne(inversedBy: 'items')]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    private ?Rarity $rarity = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    private ?Collections $collection = null;

    #[ORM\Column]
    private ?int $number = 1;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $link = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(targetEntity: ItemQuality::class, mappedBy: 'item')]
    private Collection $itemQualities;

    #[ORM\ManyToMany(targetEntity: Storage::class, inversedBy: 'items')]
    private Collection $storages;

    public function __construct()
    {
        $this->itemQualities = new ArrayCollection();
        $this->storages = new ArrayCollection();
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

    #[ORM\PrePersist]
    public function presPersit(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTime();
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
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
            if ($itemQuality->getQuality()) {
                $totalQuality += $itemQuality->getQuality();
                $totalEvaluated++;
            }
        }

        if ($totalEvaluated == 0) {
            return null;
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
            return $itemQuality->getQuality() == null;
        });

        return $notEvaluated + $itemQualitiesNotEvaluated->count();
    }

    /**
     * @return Collection<int, Storage>
     */
    public function getStorages(): Collection
    {
        return $this->storages;
    }

    public function addStorage(Storage $storage): static
    {
        if (!$this->storages->contains($storage)) {
            $this->storages->add($storage);
        }

        return $this;
    }

    public function removeStorage(Storage $storage): static
    {
        $this->storages->removeElement($storage);

        return $this;
    }
}
