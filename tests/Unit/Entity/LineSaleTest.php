<?php

namespace App\Tests\Unit\Entity;

use App\Entity\LineSale;
use App\Entity\Product;
use App\ValueObject\Money;
use App\ValueObject\Quantity;
use PHPUnit\Framework\TestCase;

class LineSaleTest extends TestCase
{
    public function testCalculateTotalRightWay(): void
    {
        $product = new Product('Test', 'SKU', new Money(1500));

        $line = new LineSale($product, new Quantity(2));

        $this->assertEquals(3000, $line->getTotal()->getValue());
    }

    public function testNotAllowInvalidAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $product = new Product('Test', 'SKU', new Money(1000));

        new LineSale($product, new Quantity(0));
    }

    public function testNotAllowNegativePrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $product = new Product('Test', 'SKU', new Money(-100));

        new LineSale($product, new Quantity(2));
    }
}
