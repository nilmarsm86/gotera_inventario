<?php

namespace App\Entity;

use App\Repository\ProductPriceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductPriceRepository::class)]
class ProductPrice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'productPrices')]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\Column]
    private int $price = 0;

    #[ORM\Column]
    private \DateTimeImmutable $startAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endAt = null;

    public function __construct(Product $product, \App\ValueObject\Money $money)
    {
        $this->product = $product;
        $this->price = $money->getValue();
        $this->startAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    //    public function setProduct(?Product $product): static
    //    {
    //        $this->product = $product;
    //
    //        return $this;
    //    }

    public function getPrice(): \App\ValueObject\Money
    {
        return new \App\ValueObject\Money($this->price);
    }

    public function setPrice(\App\ValueObject\Money $money): static
    {
        if (null !== $this->endAt) {
            throw new \RuntimeException('No se puede modificar un período cerrado');
        }

        $this->price = $money->getValue();

        return $this;
    }

    public function getStartAt(): \DateTimeImmutable
    {
        return $this->startAt;
    }

    //    public function setStartAt(\DateTimeImmutable $startAt): static
    //    {
    //        $this->startAt = $startAt;
    //
    //        return $this;
    //    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    //    public function setEndAt(?\DateTimeImmutable $endAt): static
    //    {
    //        $this->endAt = $endAt;
    //
    //        return $this;
    //    }

    public function closePeriod(): void
    {
        if (null !== $this->endAt) {
            throw new \RuntimeException('Ya está cerrado');
        }

        $this->endAt = new \DateTimeImmutable();
    }
}
