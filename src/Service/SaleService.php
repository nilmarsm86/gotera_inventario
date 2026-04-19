<?php

namespace App\Service;

use App\DTO\SaleItem;
use App\Entity\LineSale;
use App\Entity\Sale;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;

readonly class SaleService
{
    public function __construct(
        private EntityManagerInterface $em,
        private InventoryService $inventoryService,
    ) {
    }

    /**
     * @param SaleItem[] $items
     *
     * @throws ORMException
     */
    public function create(array $items): Sale
    {
        if (0 === count($items)) {
            throw new \InvalidArgumentException('La venta no puede estar vacía');
        }

        // ← VALIDACIÓN PREVIA (fuera de la transacción)
        $this->validateStockAvailability($items);

        // ← Solo si todo es válido, ejecutamos la transacción
        return $this->em->wrapInTransaction(function () use ($items) {
            $sale = new Sale();

            foreach ($items as $item) {
                $product = $item->product;
                $quantity = $item->quantity;

                $line = new LineSale($product, $quantity);
                $sale->addLineSale($line);

                // Impacto en inventario
                $this->inventoryService->registerDeparture(
                    $product,
                    $quantity,
                    'sale'
                );
            }

            $this->em->persist($sale);

            return $sale;
        });
    }

    /**
     * @param SaleItem[] $items
     *
     * @throws ORMException
     */
    private function validateStockAvailability(array $items): void
    {
        foreach ($items as $item) {
            $product = $item->product;
            $quantity = $item->quantity->getValue();

            // Refrescamos por si acaso (evita race conditions leves)
            $this->em->refresh($product);

            if ($product->getStock() < $quantity) {
                throw new \RuntimeException(sprintf('Stock insuficiente para el producto: %s (disponible: %d, solicitado: %d)', $product->getName(), $product->getStock(), $quantity));
            }
        }
    }
}
