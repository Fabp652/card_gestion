<?php

namespace App\Entity;

use App\Controller\Api\GetCardByRarityController;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\RarityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['read:rarity']],
    denormalizationContext: ['groups' => ['create:rarity']],
    operations: [
        new Get(),
        new Post(),
        new GetCollection(),
        new Patch(),
        new Delete(),
        new GetCollection(
            name: 'cardByRarity',
            uriTemplate: '/rarities/{id}/cards',
            controller: GetCardByRarityController::class,
            normalizationContext: ['groups' => ['read:card']]
        )
    ]
)]
#[ORM\Entity(repositoryClass: RarityRepository::class)]
class Rarity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:rarity'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['create:rarity', 'read:rarity', 'read:card'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['create:rarity', 'read:rarity'])]
    private ?int $grade = null;

    #[ORM\OneToMany(targetEntity: Card::class, mappedBy: 'rarity')]
    private Collection $cards;

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

    /**
     * @return Collection<int, Card>
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function addCard(Card $card): static
    {
        if (!$this->cards->contains($card)) {
            $this->cards->add($card);
            $card->setRarity($this);
        }

        return $this;
    }

    public function removeCard(Card $card): static
    {
        if ($this->cards->removeElement($card)) {
            // set the owning side to null (unless already changed)
            if ($card->getRarity() === $this) {
                $card->setRarity(null);
            }
        }

        return $this;
    }
}
