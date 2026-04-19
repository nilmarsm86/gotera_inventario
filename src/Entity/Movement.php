<?php

namespace App\Entity;

use App\Entity\Enums\MovementType;
use App\Repository\MovementRepository;
use App\ValueObject\Quantity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MovementRepository::class)]
class Movement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'movements')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private Product $product;

    #[ORM\Column(enumType: MovementType::class)]
    private MovementType $type;

    #[ORM\Column]
    private int $amount;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column]
    private int $unitPrice = 0;

    public function __construct(
        Product $product,
        MovementType $type,
        Quantity $amount,
        ?\App\ValueObject\Money $unitPrice = null,
        ?string $reference = null,
    ) {
        $this->product = $product;
        $this->type = $type;
        $this->amount = $amount->getValue();
        $this->createdAt = new \DateTimeImmutable();
        $this->unitPrice = (null === $unitPrice) ? 0 : $unitPrice->getValue();
        $this->reference = $reference;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    //    protected function setProduct(?Product $product): static
    //    {
    //        $this->product = $product;
    //
    //        return $this;
    //    }

    public function getType(): MovementType
    {
        return $this->type;
    }

    //    public function setType(MovementType $type): static
    //    {
    //        $this->type = $type;
    //
    //        return $this;
    //    }

    public function getAmount(): Quantity
    {
        return new Quantity($this->amount);
    }

    //    public function setAmount(int $amount): static
    //    {
    //        $this->amount = $amount;
    //
    //        return $this;
    //    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    //    public function setMovementAt(\DateTimeImmutable $movementAt): static
    //    {
    //        $this->movementAt = $movementAt;
    //
    //        return $this;
    //    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    //    public function setReference(?string $reference): static
    //    {
    //        $this->reference = $reference;
    //
    //        return $this;
    //    }

    public function getUnitPrice(): \App\ValueObject\Money
    {
        return new \App\ValueObject\Money($this->unitPrice);
    }

    public function getTotal(): \App\ValueObject\Money
    {
        if (MovementType::Entrance !== $this->getType()) {
            throw new \RuntimeException('Solo los movimientos de entrada tiene un precio de inversión.');
        }

        $unitPrice = new \App\ValueObject\Money($this->unitPrice);

        return $unitPrice->multiply(new Quantity($this->amount));
    }
}
