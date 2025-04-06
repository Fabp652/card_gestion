<?php

namespace App\Entity;

use App\Repository\RarityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RarityRepository::class)]
class Rarity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 45)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $grade = null;

    private Collection $cards;

    #[ORM\ManyToOne(inversedBy: 'rarities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Collections $collection = null;

    /**
     * @var Collection<int, Item>
     */
    #[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'rarity')]
    private Collection $items;

    #[ORM\ManyToOne]
    private ?FileManager $file = null;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
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

    public function getGrade(): ?int
    {
        return $this->grade;
    }

    public function setGrade(int $grade): static
    {
        $this->grade = $grade;

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
            $item->setRarity($this);
        }

        return $this;
    }

    public function removeItem(Item $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getRarity() === $this) {
                $item->setRarity(null);
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
}
