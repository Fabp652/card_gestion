<?php

namespace App\Entity;

use App\Repository\SellerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SellerRepository::class)]
class Seller
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $shopName = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column]
    private ?bool $isPrivateSeller = null;

    #[ORM\Column]
    private ?bool $isShop = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $link = null;

    #[ORM\ManyToOne]
    private ?Street $street = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShopName(): ?string
    {
        return $this->shopName;
    }

    public function setShopName(?string $shopName): static
    {
        $this->shopName = $shopName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function isPrivateSeller(): ?bool
    {
        return $this->isPrivateSeller;
    }

    public function setIsPrivateSeller(bool $isPrivateSeller): static
    {
        $this->isPrivateSeller = $isPrivateSeller;

        return $this;
    }

    public function isShop(): ?bool
    {
        return $this->isShop;
    }

    public function setIsShop(bool $isShop): static
    {
        $this->isShop = $isShop;

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

    public function getStreet(): ?Street
    {
        return $this->street;
    }

    public function setStreet(?Street $street): static
    {
        $this->street = $street;

        return $this;
    }
}
