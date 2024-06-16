<?php

namespace App\Entity;

use App\Repository\CollectionsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CollectionsRepository::class)]
class Collections
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'collections')]
    private ?Category $category = null;

    /**
     * @var Collection<int, Rarity>
     */
    #[ORM\OneToMany(targetEntity: Rarity::class, mappedBy: 'collection')]
    private Collection $rarities;

    /**
     * @var Collection<int, Item>
     */
    #[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'collection')]
    private Collection $items;

    public function __construct()
    {
        $this->rarities = new ArrayCollection();
        $this->items = new ArrayCollection();
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, Rarity>
     */
    public function getRarities(): Collection
    {
        return $this->rarities;
    }

    public function addRarity(Rarity $rarity): static
    {
        if (!$this->rarities->contains($rarity)) {
            $this->rarities->add($rarity);
            $rarity->setCollection($this);
        }

        return $this;
    }

    public function removeRarity(Rarity $rarity): static
    {
        if ($this->rarities->removeElement($rarity)) {
            // set the owning side to null (unless already changed)
            if ($rarity->getCollection() === $this) {
                $rarity->setCollection(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Item>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Item $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCollection($this);
        }

        return $this;
    }

    public function removeItem(Item $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getCollection() === $this) {
                $item->setCollection(null);
            }
        }

        return $this;
    }
}
