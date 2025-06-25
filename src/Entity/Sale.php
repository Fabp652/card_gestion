<?php

namespace App\Entity;

use App\Repository\SaleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SaleRepository::class)]
class Sale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    #[ORM\Column(nullable: true)]
    private ?bool $send = null;

    #[ORM\Column(nullable: true)]
    private ?bool $refunded = null;

    #[ORM\Column]
    private ?bool $refundRequest = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $refundReason = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $sendAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $refundAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $soldAt = null;

    #[ORM\Column]
    private ?bool $isOrder = false;

    #[ORM\Column]
    private ?bool $sold = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $link = null;

    #[ORM\ManyToOne(inversedBy: 'sales')]
    private ?Market $market = null;

    /**
     * @var Collection<int, ItemSale>
     */
    #[ORM\OneToMany(targetEntity: ItemSale::class, mappedBy: 'sale')]
    private Collection $itemSales;

    public function __construct()
    {
        $this->itemSales = new ArrayCollection();
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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function isSend(): ?bool
    {
        return $this->send;
    }

    public function setSend(?bool $send): static
    {
        $this->send = $send;

        return $this;
    }

    public function isRefunded(): ?bool
    {
        return $this->refunded;
    }

    public function setRefunded(?bool $refunded): static
    {
        $this->refunded = $refunded;

        return $this;
    }

    public function isRefundRequest(): ?bool
    {
        return $this->refundRequest;
    }

    public function setRefundRequest(bool $refundRequest): static
    {
        $this->refundRequest = $refundRequest;

        return $this;
    }

    public function getRefundReason(): ?string
    {
        return $this->refundReason;
    }

    public function setRefundReason(?string $refundReason): static
    {
        $this->refundReason = $refundReason;

        return $this;
    }

    public function getSendAt(): ?\DateTimeInterface
    {
        return $this->sendAt;
    }

    public function setSendAt(?\DateTimeInterface $sendAt): static
    {
        $this->sendAt = $sendAt;

        return $this;
    }

    public function getRefundAt(): ?\DateTimeInterface
    {
        return $this->refundAt;
    }

    public function setRefundAt(?\DateTimeInterface $refundAt): static
    {
        $this->refundAt = $refundAt;

        return $this;
    }

    public function getSoldAt(): ?\DateTimeInterface
    {
        return $this->soldAt;
    }

    public function setSoldAt(?\DateTimeInterface $soldAt): static
    {
        $this->soldAt = $soldAt;

        return $this;
    }

    public function isOrder(): ?bool
    {
        return $this->isOrder;
    }

    public function setIsOrder(bool $isOrder): static
    {
        $this->isOrder = $isOrder;

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

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getMarket(): ?Market
    {
        return $this->market;
    }

    public function setMarket(?Market $market): static
    {
        $this->market = $market;

        return $this;
    }

    /**
     * @return Collection<int, ItemSale>
     */
    public function getItemSales(): Collection
    {
        return $this->itemSales;
    }

    public function addItemSale(ItemSale $itemSale): static
    {
        if (!$this->itemSales->contains($itemSale)) {
            $this->itemSales->add($itemSale);
            $itemSale->setSale($this);
        }

        return $this;
    }

    public function removeItemSale(ItemSale $itemSale): static
    {
        if ($this->itemSales->removeElement($itemSale)) {
            // set the owning side to null (unless already changed)
            if ($itemSale->getSale() === $this) {
                $itemSale->setSale(null);
            }
        }

        return $this;
    }
}
