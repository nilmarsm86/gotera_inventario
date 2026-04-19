<?php

namespace App\DTO;

use App\Entity\Product;
use App\ValueObject\Quantity;

readonly class SaleItem
{
    public function __construct(
        public Product $product,
        public Quantity $quantity,
    ) {
    }
}
