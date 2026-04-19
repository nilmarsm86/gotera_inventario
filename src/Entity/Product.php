<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use App\ValueObject\Quantity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $sku;

    #[ORM\Column]
    private int $price = 0;

    #[ORM\Column]
    private int $stock = 0;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    /**
     * @var Collection<int, Movement>
     */
    #[ORM\OneToMany(targetEntity: Movement::class, mappedBy: 'product')]
    private Collection $movements;

    /**
     * @var Collection<int, ProductPrice>
     */
    #[ORM\OneToMany(targetEntity: ProductPrice::class, mappedBy: 'product', cascade: ['persist'])]
    private Collection $productPrices;

    /**
     * @var Collection<int, LineSale>
     */
    #[ORM\OneToMany(targetEntity: LineSale::class, mappedBy: 'product')]
    private Collection $lineSales;

    public function __construct(string $name, string $sku, ?\App\ValueObject\Money $price = null)
    {
        $this->name = $name;
        $this->sku = $sku;

        $this->price = (null !== $price) ? $price->getValue() : 0;
        $this->productPrices = new ArrayCollection();
        $this->productPrices->add(new ProductPrice($this, (null !== $price) ? $price : new \App\ValueObject\Money(0)));

        $this->movements = new ArrayCollection();
        $this->lineSales = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    //    public function setSku(string $sku): static
    //    {
    //        $this->sku = $sku;
    //
    //        return $this;
    //    }

    public function getPrice(): \App\ValueObject\Money
    {
        return new \App\ValueObject\Money($this->price);
    }

    public function setPrice(\App\ValueObject\Money $price): static
    {
        $this->price = $price->getValue();

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    //    public function setStock(int $stock): static
    //    {
    //        $this->stock = $stock;
    //
    //        return $this;
    //    }

    public function getVersion(): int
    {
        return $this->version;
    }
    //
    //    public function setVersion(int $version): static
    //    {
    //        $this->version = $version;
    //
    //        return $this;
    //    }

    // SOLO el servicio de inventario debería usar esto
    public function increaseStock(Quantity $amount): void
    {
        $this->stock += $amount->getValue();
    }

    public function decreaseStock(Quantity $amount): void
    {
        if ($this->stock < $amount->getValue()) {
            throw new \RuntimeException('Stock insuficiente');
        }

        $this->stock -= $amount->getValue();
    }

    /**
     * @return Collection<int, Movement>
     */
    public function getMovements(): Collection
    {
        return $this->movements;
    }

    //    public function addMovement(Movement $movement): static
    //    {
    //        if (!$this->movements->contains($movement)) {
    //            $this->movements->add($movement);
    //            $movement->setProduct($this);
    //        }
    //
    //        return $this;
    //    }

    //    public function removeMovement(Movement $movement): static
    //    {
    //        if ($this->movements->removeElement($movement)) {
    //            // set the owning side to null (unless already changed)
    //            if ($movement->getProduct() === $this) {
    //                $movement->setProduct(null);
    //            }
    //        }
    //
    //        return $this;
    //    }

    /**
     * @return Collection<int, ProductPrice>
     */
    public function getProductPrices(): Collection
    {
        return $this->productPrices;
    }

    //    public function addProductPrice(ProductPrice $productPrice): static
    //    {
    //        if (!$this->productPrices->contains($productPrice)) {
    //            $this->productPrices->add($productPrice);
    //            $productPrice->setProduct($this);
    //        }
    //
    //        return $this;
    //    }

    //    public function removeProductPrice(ProductPrice $productPrice): static
    //    {
    //        if ($this->productPrices->removeElement($productPrice)) {
    //            // set the owning side to null (unless already changed)
    //            if ($productPrice->getProduct() === $this) {
    //                $productPrice->setProduct(null);
    //            }
    //        }
    //
    //        return $this;
    //    }

    /**
     * @return Collection<int, LineSale>
     */
    public function getLineSales(): Collection
    {
        return $this->lineSales;
    }

    //    public function addLineSale(LineSale $onlineSale): static
    //    {
    //        if (!$this->lineSales->contains($onlineSale)) {
    //            $this->lineSales->add($onlineSale);
    //            $onlineSale->setProduct($this);
    //        }
    //
    //        return $this;
    //    }

    //    public function removeLineSale(LineSale $onlineSale): static
    //    {
    //        if ($this->lineSales->removeElement($onlineSale)) {
    //            // set the owning side to null (unless already changed)
    //            if ($onlineSale->getProduct() === $this) {
    //                $onlineSale->setProduct(null);
    //            }
    //        }
    //
    //        return $this;
    //    }
}
