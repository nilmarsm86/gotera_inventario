<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Product;
use App\Entity\ProductPrice;
use App\ValueObject\Money;
use PHPUnit\Framework\TestCase;

class ProductPriceTest extends TestCase
{
    public function testNotAllowNegativePrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $product = new Product('Test', 'SKU', new Money(1000));

        new ProductPrice($product, new Money(-100));
    }

    public function testClosePeriod(): void
    {
        $product = new Product('Test', 'SKU', new Money(1000));

        $price = new ProductPrice($product, new Money(1000));

        $this->assertNull($price->getEndAt());

        $price->closePeriod();

        $this->assertNotNull($price->getEndAt());
    }

    public function testNotAllowCloseMoreThan1(): void
    {
        $this->expectException(\RuntimeException::class);

        $product = new Product('Test', 'SKU', new Money(1000));

        $price = new ProductPrice($product, new Money(1000));

        $price->closePeriod();
        $price->closePeriod();
    }
}
