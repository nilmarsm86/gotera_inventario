<?php

namespace App\ValueObject;

final readonly class Quantity
{
    public function __construct(private int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser positiva');
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }
}
