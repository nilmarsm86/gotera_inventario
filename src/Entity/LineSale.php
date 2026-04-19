<?php

namespace App\Entity;

use App\Repository\LineSaleRepository;
use App\ValueObject\Quantity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LineSaleRepository::class)]
class LineSale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'onlineSales')]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\Column]
    private int $amount = 0;

    #[ORM\Column]
    private int $unitPrice = 0;

    #[ORM\Column]
    private int $total = 0;

    #[ORM\Column]
    private \DateTimeImmutable $dateAt;

    #[ORM\Column(length: 255)]
    private string $productName;

    #[ORM\Column(length: 255)]
    private string $productSku;

    #[ORM\ManyToOne(inversedBy: 'linesSales')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Sale $sale = null;

    public function __construct(
        Product $product,
        Quantity $amount,
        ?\App\ValueObject\Money $unitPrice = null,
    ) {
        if (0 === $product->getPrice()->getValue()) {
            throw new \RuntimeException('Producto con precio 0.00');
        }

        if (null === $unitPrice) {
            $unitPrice = $product->getPrice();
        }

        $this->product = $product;
        $this->productName = $product->getName();
        $this->productSku = $product->getSku();
        $this->amount = $amount->getValue();
        $this->unitPrice = $unitPrice->getValue();
        $this->total = $unitPrice->multiply($amount->getValue())->getValue();
        $this->dateAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    //    public function setProduct(?Product $product): static
    //    {
    //        $this->product = $product;
    //
    //        return $this;
    //    }

    public function getAmount(): ?Quantity
    {
        //        return $this->amount;
        return new Quantity($this->amount);
    }

    //    public function setAmount(int $amount): static
    //    {
    //        $this->amount = $amount;
    //
    //        return $this;
    //    }

    public function getUnitPrice(): ?\App\ValueObject\Money
    {
        return new \App\ValueObject\Money($this->unitPrice);
    }

    //    public function setUnitPrice(\App\ValueObject\Money $unitPrice): static
    //    {
    //        $this->unitPrice = $unitPrice->getAmount();
    //
    //        return $this;
    //    }

    public function getTotal(): \App\ValueObject\Money
    {
        return new \App\ValueObject\Money($this->total);
    }

    //    public function setTotal(\App\ValueObject\Money $money): static
    //    {
    //        $this->total = $money->getAmount();
    //
    //        return $this;
    //    }

    public function getDateAt(): ?\DateTimeImmutable
    {
        return $this->dateAt;
    }

    //    public function setDateAt(\DateTimeImmutable $dateAt): static
    //    {
    //        $this->dateAt = $dateAt;
    //
    //        return $this;
    //    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    //    public function setProductName(string $productName): static
    //    {
    //        $this->productName = $productName;
    //
    //        return $this;
    //    }

    public function getProductSku(): ?string
    {
        return $this->productSku;
    }

    //    public function setProductSku(string $productSku): static
    //    {
    //        $this->productSku = $productSku;
    //
    //        return $this;
    //    }

    public function getSale(): ?Sale
    {
        return $this->sale;
    }

    public function setSale(?Sale $sale): static
    {
        $this->sale = $sale;

        return $this;
    }
}
