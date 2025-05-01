<?php

namespace App\Entity;

use App\Repository\ItemSaleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
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
    #[Assert\NotBlank(message: 'La vente doit avoir un prix')]
    #[Assert\Positive(message: 'Le prix doit être supérieur à 0')]
    private ?float $price = null;

    #[ORM\Column]
    private ?bool $sold = false;

    #[ORM\OneToMany(targetEntity: ItemQuality::class, mappedBy: 'itemSale')]
    private Collection $itemQualities;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: 'L\'URL du lien n\'est pas valide')]
    private ?string $link = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom doit avoir au moins 1 caractères', allowNull: true)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?bool $refund = null;

    #[ORM\Column(nullable: true)]
    private ?bool $refundRequested = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $refundReason = null;

    #[ORM\Column]
    private ?bool $send = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $sendAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $soldAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $refundAt = null;

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

    public function isRefund(): ?bool
    {
        return $this->refund;
    }

    public function setRefund(?bool $refund): static
    {
        $this->refund = $refund;

        return $this;
    }

    public function isRefundRequested(): ?bool
    {
        return $this->refundRequested;
    }

    public function setRefundRequested(?bool $refundRequested): static
    {
        $this->refundRequested = $refundRequested;

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

    public function getSoldAt(): ?\DateTimeInterface
    {
        return $this->soldAt;
    }

    public function setSoldAt(?\DateTimeInterface $soldAt): static
    {
        $this->soldAt = $soldAt;

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
}
