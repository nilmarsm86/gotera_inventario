<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Enums\MovementType;
use App\Entity\Movement;
use App\Entity\Product;
use App\ValueObject\Money;
use App\ValueObject\Quantity;
use PHPUnit\Framework\TestCase;

class MovementTest extends TestCase
{
    public function testDoesNotPermitInvalidQuantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $product = new Product('Test', 'SKU', new Money(1000));

        new Movement($product, MovementType::Entrance, new Quantity(0));
    }

    public function testTotalInvestInEntrance(): void
    {
        $product = new Product('Test', 'SKU', new Money(1000));

        $movement = new Movement($product, MovementType::Entrance, new Quantity(3), new Money(800));
        $this->assertEquals(2400, $movement->getTotal()->getValue());
    }

    public function testTotalInvestInDeparture(): void
    {
        $this->expectException(\RuntimeException::class);

        $product = new Product('Test', 'SKU', new Money(1000));

        $movement = new Movement($product, MovementType::Departure, new Quantity(3), new Money(800));
        $this->assertEquals(2400, $movement->getTotal()->getValue());
    }
}
