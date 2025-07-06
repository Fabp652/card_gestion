<?php

namespace App\Entity;

use App\Repository\CollectionsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CollectionsRepository::class)]
#[UniqueEntity(fields: ['name', 'category'], message: 'La collection existe déjà')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: false)]
class Collections
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 45)]
    #[Assert\NotBlank(message: 'La collection doit avoir un nom')]
    #[Assert\Length(max: 45, maxMessage: 'Le nom doit avoir au maximum 45 caractères')]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'collections')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'La collection doit avoir une catégorie')]
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

    #[ORM\ManyToOne]
    private ?FileManager $file = null;

    #[ORM\Column]
    private ?bool $complete = false;

    #[ORM\Column]
    private ?bool $hasRarities = false;

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

    public function getFile(): ?FileManager
    {
        return $this->file;
    }

    public function setFile(?FileManager $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function isComplete(): ?bool
    {
        return $this->complete;
    }

    public function setComplete(bool $complete): static
    {
        $this->complete = $complete;

        return $this;
    }

    public function hasRarities(): ?bool
    {
        return $this->hasRarities;
    }

    public function setHasRarities(bool $hasRarities): static
    {
        $this->hasRarities = $hasRarities;

        return $this;
    }
}
