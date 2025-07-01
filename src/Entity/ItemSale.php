<?php

namespace App\Entity;

use App\Repository\ItemSaleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ItemSaleRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: false)]
class ItemSale
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(scale: 2)]
    #[Assert\NotBlank(message: 'L\'objet doit avoir un prix')]
    #[Assert\Positive(message: 'Le prix doit être supérieur à 0')]
    private ?float $price = null;

    #[ORM\Column(nullable: true)]
    private ?bool $refunded = null;

    #[ORM\Column]
    private ?bool $refundRequest = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $refundReason = null;

    #[ORM\Column]
    private ?bool $send = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $sendAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $refundAt = null;

    #[ORM\ManyToOne(inversedBy: 'itemSales')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Sale $sale = null;

    #[ORM\OneToOne(inversedBy: 'itemSale', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn]
    private ?ItemQuality $itemQuality = null;

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

    public function setRefundRequest(?bool $refundRequest): static
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

    public function isSend(): ?bool
    {
        return $this->send;
    }

    public function setSend(bool $send): static
    {
        $this->send = $send;

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

    public function getSale(): ?Sale
    {
        return $this->sale;
    }

    public function setSale(?Sale $sale): static
    {
        $this->sale = $sale;

        return $this;
    }

    public function getItemQuality(): ?ItemQuality
    {
        return $this->itemQuality;
    }

    public function setItemQuality(?ItemQuality $itemQuality): static
    {
        $this->itemQuality = $itemQuality;

        return $this;
    }
}
