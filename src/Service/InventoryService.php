<?php

namespace App\Service;

use App\Entity\Enums\MovementType;
use App\Entity\Movement;
use App\Entity\Product;
use App\ValueObject\Money;
use App\ValueObject\Quantity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;

readonly class InventoryService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function registerEntry(
        Product $product,
        Quantity $amount,
        ?string $reference = null,
    ): void {
        $this->validatePositiveQuantity($amount, 'entrada');

        $this->em->wrapInTransaction(function () use ($product, $amount, $reference) {
            $this->em->refresh($product);   // solo dentro, cuando ya validamos

            $movement = new Movement(
                $product,
                MovementType::Entrance,
                $amount,
                $product->getPrice(),
                $reference
            );

            $product->increaseStock($amount);

            $this->em->persist($movement);
            $this->em->persist($product);
        });
    }

    /**
     * @throws ORMException
     */
    public function registerDeparture(
        Product $product,
        Quantity $amount,
        ?string $reference = null,
    ): void {
        $this->validatePositiveQuantity($amount, 'salida');

        // Validación crítica de stock ANTES de abrir transacción
        $this->validateSufficientStock($product, $amount);

        $this->em->wrapInTransaction(function () use ($product, $amount, $reference) {
            $this->em->refresh($product);

            $movement = new Movement(
                $product,
                MovementType::Departure,
                $amount,
                new Money(0),
                $reference
            );

            $product->decreaseStock($amount);

            $this->em->persist($movement);
            $this->em->persist($product);
        });
    }

    public function adjustStock(
        Product $product,
        Quantity $newStock,
        ?string $reference = null,
    ): void {
        $this->validateNonNegativeQuantity($newStock, 'ajuste');

        $this->em->wrapInTransaction(function () use ($product, $newStock, $reference) {
            $this->em->refresh($product);

            $actualStock = $product->getStock();
            $difference = $newStock->getValue() - $actualStock;

            if (0 === $difference) {
                return; // sin movimiento innecesario
            }

            $type = $difference > 0
                ? MovementType::AdjustmentEntrance
                : MovementType::AdjustmentDeparture;

            $movementQuantity = new Quantity(abs($difference));

            $movement = new Movement(
                $product,
                $type,
                $movementQuantity,
                new Money(0),
                $reference ?? 'Ajuste manual'
            );

            ($difference > 0) ? $product->increaseStock($movementQuantity) : $product->decreaseStock($movementQuantity);

            $this->em->persist($movement);
            $this->em->persist($product);
        });
    }

    // ====================== VALIDACIONES PRIVADAS ======================

    private function validatePositiveQuantity(Quantity $quantity, string $operation): void
    {
        if ($quantity->getValue() <= 0) {
            throw new \RuntimeException("La cantidad para $operation debe ser positiva y mayor que cero.");
        }
    }

    private function validateNonNegativeQuantity(Quantity $quantity, string $operation): void
    {
        if ($quantity->getValue() < 0) {
            throw new \RuntimeException('El stock final en un ajuste no puede ser negativo.');
        }
    }

    /**
     * @throws ORMException
     */
    private function validateSufficientStock(Product $product, Quantity $amount): void
    {
        $this->em->refresh($product);   // refresco aquí para tener dato fresco en validación

        if ($product->getStock() < $amount->getValue()) {
            throw new \RuntimeException(sprintf('Stock insuficiente para salida. Disponible: %d | Solicitado: %d | Producto: %s', $product->getStock(), $amount->getValue(), $product->getName()));
        }
    }
}
