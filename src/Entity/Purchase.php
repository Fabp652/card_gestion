<?php

namespace App\Entity;

use App\Repository\PurchaseRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PurchaseRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: false)]
class Purchase
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'Le prix doit être supérieur à 0')]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l\'achat doit être renseigné.')]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $received = false;

    #[ORM\Column(nullable: true)]
    private ?bool $refunded = null;

    #[ORM\Column]
    private ?bool $refundRequest = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $refundedReason = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $receivedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $refundedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $buyAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: 'L\'URL du lien n\'est pas valide')]
    private ?string $link = null;

    /**
     * @var Collection<int, ItemPurchase>
     */
    #[ORM\OneToMany(targetEntity: ItemPurchase::class, mappedBy: 'purchase')]
    private Collection $itemsPurchase;

    #[ORM\Column]
    private ?bool $isOrder = false;

    #[ORM\Column]
    private ?bool $isValid = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $validatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'purchases')]
    private ?Market $market = null;

    public function __construct()
    {
        $this->itemsPurchase = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getRefundedReason(): ?string
    {
        return $this->refundedReason;
    }

    public function setRefundedReason(?string $refundedReason): static
    {
        $this->refundedReason = $refundedReason;

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
        return $this->refundedAt;
    }

    public function setRefundedAt(?\DateTimeInterface $refundedAt): static
    {
        $this->refundedAt = $refundedAt;

        return $this;
    }

    public function getBuyAt(): ?\DateTimeInterface
    {
        return $this->buyAt;
    }

    public function setBuyAt(?\DateTimeInterface $buyAt): static
    {
        $this->buyAt = $buyAt;

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

    /**
     * @return Collection<int, ItemPurchase>
     */
    public function getItemsPurchase(): Collection
    {
        return $this->itemsPurchase;
    }

    public function addItemsPurchase(ItemPurchase $itemsPurchase): static
    {
        if (!$this->itemsPurchase->contains($itemsPurchase)) {
            $this->itemsPurchase->add($itemsPurchase);
            $itemsPurchase->setPurchase($this);
        }

        return $this;
    }

    public function removeItemsPurchase(ItemPurchase $itemsPurchase): static
    {
        if ($this->itemsPurchase->removeElement($itemsPurchase)) {
            // set the owning side to null (unless already changed)
            if ($itemsPurchase->getPurchase() === $this) {
                $itemsPurchase->setPurchase(null);
            }
        }

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
        foreach ($this->itemsPurchase as $itemPurchase) {
            $price += $itemPurchase->getPrice();
        }
        $this->price = $price;

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
}
