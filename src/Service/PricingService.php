<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\ProductPrice;
use App\ValueObject\Money;
use Doctrine\ORM\EntityManagerInterface;

readonly class PricingService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function changePrice(Product $product, Money $newPrice): void
    {
        if ($product->getPrice()->isSame($newPrice)) {
            return; // No hay cambio, no hacemos nada
        }

        // ← VALIDACIÓN PREVIA (fuera de transacción)
        $this->validateNoMultipleActivePrices($product);

        // ← Solo si pasa validación, ejecutamos la transacción atómica
        $this->em->wrapInTransaction(function () use ($product, $newPrice) {
            $this->closeCurrentPriceIfExists($product);
            $this->createNewPrice($product, $newPrice);
            $this->updateProductPriceCache($product, $newPrice);
        });
    }

    private function validateNoMultipleActivePrices(Product $product): void
    {
        $activePrices = $this->em->getRepository(ProductPrice::class)
            ->findBy([
                'product' => $product,
                'endAt' => null,
            ]);

        if (count($activePrices) > 1) {
            throw new \RuntimeException(sprintf('Inconsistencia crítica: múltiples precios activos para el producto %s (ID: %s)', $product->getName(), $product->getId() ?? 'sin ID'));
        }
    }

    private function closeCurrentPriceIfExists(Product $product): void
    {
        $activePrice = $this->em->getRepository(ProductPrice::class)
            ->findOneBy([
                'product' => $product,
                'endAt' => null,
            ], ['id' => 'DESC']);   // findOneBy es más correcto que findBy + [0]

        if (null !== $activePrice) {
            $activePrice->closePeriod();
            $this->em->persist($activePrice);
        }
    }

    private function createNewPrice(Product $product, Money $newPrice): void
    {
        $newProductPrice = new ProductPrice($product, $newPrice);
        $this->em->persist($newProductPrice);
    }

    private function updateProductPriceCache(Product $product, Money $newPrice): void
    {
        $product->setPrice($newPrice);
        $this->em->persist($product);
    }
}
