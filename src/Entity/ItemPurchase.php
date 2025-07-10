<?php

namespace App\Entity;

use App\Repository\ItemPurchaseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ItemPurchaseRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: false)]
class ItemPurchase
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(scale: 2)]
    #[Assert\NotBlank(message: 'L\'achat doit avoir un prix')]
    #[Assert\Positive(message: 'Le prix doit être supérieur à 0')]
    private ?float $price = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'L\'achat doit avoir une quantité')]
    #[Assert\Positive(message: 'La quantité doit être supérieur à 0')]
    private ?int $quantity = 1;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: 'L\'URL du lien n\'est pas valide')]
    private ?string $link = null;

    #[ORM\Column]
    private ?bool $received = false;

    #[ORM\ManyToOne(inversedBy: 'itemPurchases')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Item $item = null;

    #[ORM\Column(nullable: true)]
    private ?bool $refunded = null;

    #[ORM\Column(nullable: true)]
    private ?bool $refundRequest = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $refundReason = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\LessThanOrEqual('today', message: 'La date ne peut pas être ultérieur à aujourd\'hui')]
    private ?\DateTimeInterface $receivedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\LessThanOrEqual('today', message: 'La date ne peut pas être ultérieur à aujourd\'hui')]
    private ?\DateTimeInterface $refundAt = null;

    #[ORM\ManyToOne(inversedBy: 'itemsPurchase')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Purchase $purchase = null;

    #[ORM\Column(nullable: true)]
    private ?int $quantityToRefund = null;

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

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

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

    public function isReceived(): ?bool
    {
        return $this->received;
    }

    public function setReceived(bool $received): static
    {
        $this->received = $received;

        return $this;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): static
    {
        $this->item = $item;

        return $this;
    }

    public function isRefunded(): ?bool
    {
        return $this->refunded;
    }

    public function setRefunded(bool $refunded): static
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

    public function getReceivedAt(): ?\DateTimeInterface
    {
        return $this->receivedAt;
    }

    public function setReceivedAt(?\DateTimeInterface $receivedAt): static
    {
        $this->receivedAt = $receivedAt;

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

    public function getPurchase(): ?Purchase
    {
        return $this->purchase;
    }

    public function setPurchase(?Purchase $purchase): static
    {
        $this->purchase = $purchase;

        return $this;
    }

    public function getQuantityToRefund(): ?int
    {
        return $this->quantityToRefund;
    }

    public function setQuantityToRefund(int $quantityToRefund): static
    {
        $this->quantityToRefund = $quantityToRefund;

        return $this;
    }
}
