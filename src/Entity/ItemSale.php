<?php

namespace App\Entity;

use App\Repository\ItemSaleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemSaleRepository::class)]
class ItemSale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column]
    private ?bool $sold = false;

    #[ORM\OneToMany(targetEntity: ItemQuality::class, mappedBy: 'itemSale')]
    private Collection $itemQualities;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function __construct()
    {
        $this->itemQualities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function isSold(): ?bool
    {
        return $this->sold;
    }

    public function setSold(bool $sold): static
    {
        $this->sold = $sold;

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
            $itemQuality->setItemSale($this);
        }

        return $this;
    }

    public function removeItemQuality(ItemQuality $itemQuality): static
    {
        if ($this->itemQualities->removeElement($itemQuality)) {
            // set the owning side to null (unless already changed)
            if ($itemQuality->getItemSale() === $this) {
                $itemQuality->setItemSale(null);
            }
        }

        return $this;
    }

    public function removeAllItemQualities(): static
    {
        foreach ($this->itemQualities as $itemQuality) {
            $this->removeItemQuality($itemQuality);
        }

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): static
    {
        $this->link = $link;

        return $this;
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
}
