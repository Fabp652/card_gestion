<?php

namespace App\Entity;

use App\Repository\SaleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SaleRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: false)]
class Sale
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La vente doit avoir un nom')]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: 'La vente doit avoir un prix', allowNull: true)]
    #[Assert\Positive(message: 'Le prix doit être supérieur à 0')]
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
    #[Assert\LessThanOrEqual('today', message: 'La date ne peut pas être ultérieur à aujourd\'hui')]
    private ?\DateTimeInterface $sendAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\LessThanOrEqual('today', message: 'La date ne peut pas être ultérieur à aujourd\'hui')]
    private ?\DateTimeInterface $refundAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\LessThanOrEqual('today', message: 'La date ne peut pas être ultérieur à aujourd\'hui')]
    private ?\DateTimeInterface $soldAt = null;

    #[ORM\Column]
    private ?bool $isOrder = false;

    #[ORM\Column]
    private ?bool $sold = false;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: 'L\'URL du lien n\'est pas valide')]
    private ?string $link = null;

    #[ORM\ManyToOne(inversedBy: 'sales')]
    private ?Market $market = null;

    #[ORM\Column]
    private ?bool $isValid = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $validatedAt = null;

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

    public function getRefundedAt(): ?\DateTimeInterface
    {
        return $this->refundAt;
    }

    public function setRefundedAt(?\DateTimeInterface $refundAt): static
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

    public function isValid(): ?bool
    {
        return $this->isValid;
    }

    public function setIsValid(?bool $isValid): static
    {
        $this->isValid = $isValid;

        return $this;
    }

    public function getValidatedAt(): ?\DateTimeInterface
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeInterface $validatedAt): static
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }

    public function caclPrice(): static
    {
        $price = 0;
        foreach ($this->itemSales as $itemSale) {
            $price += $itemSale->getPrice();
        }
        $this->price = $price;

        return $this;
    }
}
