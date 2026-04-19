<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Product;
use App\ValueObject\Money;
use App\ValueObject\Quantity;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testNotAllowNegativeStock(): void
    {
        $this->expectException(\RuntimeException::class);

        $product = new Product('Test', 'SKU', new Money(1000));

        $product->decreaseStock(new Quantity(5));
    }

    public function testIncreaseAndDecreaseStock(): void
    {
        $product = new Product('Test', 'SKU', new Money(1000));

        $product->increaseStock(new Quantity(10));
        $product->decreaseStock(new Quantity(4));

        $this->assertEquals(6, $product->getStock());
    }
}
