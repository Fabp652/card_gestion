<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use App\Repository\CardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['read:card']],
    denormalizationContext: ['groups' => ['create:card']]
)]
#[ApiFilter(
    OrderFilter::class,
    properties: ['name', 'reference', 'number', 'price', 'quality', 'date'],
    arguments: ['orderParameterName' => 'order']
)]
#[ORM\Entity(repositoryClass: CardRepository::class)]
class Card
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:card'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['create:card', 'read:card'])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'cards')]
    private ?Extension $extension = null;

    #[ORM\ManyToOne(inversedBy: 'cards')]
    #[Groups(['create:card', 'read:card'])]
    private ?Rarity $rarity = null;

    #[ORM\Column(length: 255)]
    #[Groups(['create:card', 'read:card'])]
    private ?string $reference = null;

    #[ORM\Column]
    #[Groups(['create:card', 'read:card'])]
    private ?int $number = 1;

    #[ORM\Column]
    #[Groups(['create:card', 'read:card'])]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    #[Groups(['create:card', 'read:card'])]
    private ?string $quality = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['create:card', 'read:card'])]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 255)]
    #[Groups(['create:card', 'read:card'])]
    private ?string $link = null;

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

    public function getExtension(): ?Extension
    {
        return $this->extension;
    }

    public function setExtension(?Extension $extension): static
    {
        $this->extension = $extension;

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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

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

    public function getQuality(): ?string
    {
        return $this->quality;
    }

    public function setQuality(string $quality): static
    {
        $this->quality = $quality;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    #[ORM\PrePersist, ORM\PreUpdate]
    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

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
}
