<?php

namespace App\Entity;

use App\Repository\StorageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StorageRepository::class)]
class Storage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 45)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?int $capacity = null;

    #[ORM\Column]
    private ?bool $full = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?StorageType $storageType = null;

    #[ORM\OneToMany(targetEntity: ItemQuality::class, mappedBy: 'storage')]
    private Collection $itemQualities;

    public function __construct()
    {
        $this->itemQualities = new ArrayCollection();
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

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function isFull(): ?bool
    {
        return $this->full;
    }

    public function setFull(bool $full): static
    {
        $this->full = $full;

        return $this;
    }

    public function getStorageType(): ?StorageType
    {
        return $this->storageType;
    }

    public function setStorageType(?StorageType $storageType): static
    {
        $this->storageType = $storageType;

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
            $itemQuality->setStorage($this);
        }

        return $this;
    }

    public function removeItemQuality(ItemQuality $itemQuality): static
    {
        if ($this->itemQualities->removeElement($itemQuality)) {
            // set the owning side to null (unless already changed)
            if ($itemQuality->getStorage() === $this) {
                $itemQuality->setStorage(null);
            }
        }

        return $this;
    }
}
