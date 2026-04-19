<?php

namespace App\Tests\Application;

use App\DTO\SaleItem as SaleItemDto;
use App\Entity\LineSale;
use App\Entity\Movement;
use App\Entity\Product;
use App\Entity\Sale;
use App\Service\InventoryService;
use App\Service\PricingService;
use App\Service\SaleService;
use App\ValueObject\Money;
use App\ValueObject\Quantity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SaleServiceFailureTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private SaleService $saleService;
    private InventoryService $inventoryService;
    private PricingService $priceService;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->saleService = $container->get(SaleService::class);
        $this->inventoryService = $container->get(InventoryService::class);
        $this->priceService = $container->get(PricingService::class);

        $this->truncateEntities();
    }

    private function truncateEntities(): void
    {
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    //    /**
    //     * @throws ORMException
    //     */
    //    public function testRollbackCompleteIfOneLineFail(): void
    //    {
    //        // Crear productos
    //        $productA = new Product('A', 'SKU-A');
    //        $productB = new Product('B', 'SKU-B');
    //
    //        $this->em->persist($productA);
    //        $this->em->persist($productB);
    //        $this->em->flush();
    //
    //        // Precios
    //        $this->priceService->changePrice($productA, new Money(1000));
    //        $this->priceService->changePrice($productB, new Money(2000));
    //
    //        // Stock
    //        $this->inventoryService->registerEntry($productA, new Quantity(10));
    //        $this->inventoryService->registerEntry($productB, new Quantity(1));
    //
    //        //        $this->em->clear(); // ← importante: limpiar antes de la acción
    //
    //        $this->em->refresh($productA);
    //        $this->em->refresh($productB);
    //
    //        // Intento de venta (una línea válida, otra inválida)
    //        $items = [
    //            new SaleItemDto($productA, new Quantity(2)), // OK
    //            new SaleItemDto($productB, new Quantity(5)), // ❌ rompe
    //        ];
    //
    //        // Act + Assert esperado
    //        //        $this->expectException(\RuntimeException::class);
    //        // Opcional: $this->expectExceptionMessage('...mensaje específico...');
    //
    //        try {
    //            $this->saleService->create($items);
    //            $this->fail('La venta debería haber fallado por stock insuficiente del producto B.');
    //        } catch (\RuntimeException $e) {
    //            $this->em->close();
    //            $this->em = static::getContainer()->get(EntityManagerInterface::class);
    //            // Ahora sí podemos comprobar el estado
    //            $productA = $this->em->getRepository(Product::class)->find($productA->getId());
    //            $productB = $this->em->getRepository(Product::class)->find($productB->getId());
    //        }
    //
    //
    //        //
    //        //        // 🔴 VALIDACIONES CRÍTICAS
    //        //
    //        //        // 1. Stock NO debe cambiar
    //        //        $this->em->refresh($productA);
    //        //        $this->em->refresh($productB);
    //        //
    //        $this->assertEquals(10, $productA->getStock());
    //        $this->assertEquals(1, $productB->getStock());
    //
    //        // 2. No debe existir ninguna venta
    //        $sales = $this->em->getRepository(Sale::class)->findAll();
    //        $this->assertCount(0, $sales);
    //
    //        // 3. No debe haber líneas
    //        $lines = $this->em->getRepository(LineSale::class)->findAll();
    //        $this->assertCount(0, $lines);
    //
    //        // 4. No debe haber movimientos de salida adicionales
    //        $movements = $this->em->getRepository(Movement::class)->findAll();
    //
    //        // Solo deberían existir los de entrada (2)
    //        $this->assertCount(2, $movements);
    //    }

    /**
     * @throws ORMException
     */
    public function testRollbackCompleteIfOneLineFail2(): void
    {
        // Crear productos
        $productA = new Product('A', 'SKU-A');
        $productB = new Product('B', 'SKU-B');

        $this->em->persist($productA);
        $this->em->persist($productB);
        $this->em->flush();

        // Precios
        $this->priceService->changePrice($productA, new Money(1000));
        $this->priceService->changePrice($productB, new Money(2000));

        // Stock
        $this->inventoryService->registerEntry($productA, new Quantity(10));
        $this->inventoryService->registerEntry($productB, new Quantity(1));

        $this->em->refresh($productA);
        $this->em->refresh($productB);

        $items = [
            new SaleItemDto($productA, new Quantity(2)),
            new SaleItemDto($productB, new Quantity(5)), // falla
        ];

        // Act + Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $this->saleService->create($items);

        // Si llegamos aquí → test falla
        $this->fail('Debería haber lanzado excepción');

        // ← Nunca llega aquí
    }

    // Test de verificación del estado (separado o después de reiniciar EM)

    /**
     * @throws ORMException
     */
    public function testNoChangesWhenStockValidationFails(): void
    {
        // Crear productos
        $productA = new Product('A', 'SKU-A');
        $productB = new Product('B', 'SKU-B');

        $this->em->persist($productA);
        $this->em->persist($productB);
        $this->em->flush();

        // Precios
        $this->priceService->changePrice($productA, new Money(1000));
        $this->priceService->changePrice($productB, new Money(2000));

        // Stock
        $this->inventoryService->registerEntry($productA, new Quantity(10));
        $this->inventoryService->registerEntry($productB, new Quantity(1));

        $this->em->refresh($productA);
        $this->em->refresh($productB);

        $items = [
            new SaleItemDto($productA, new Quantity(2)),
            new SaleItemDto($productB, new Quantity(5)), // falla
        ];

        try {
            $this->saleService->create($items);
        } catch (\RuntimeException $e) {
            // esperado
        }

        // Reiniciamos EM porque wrapInTransaction pudo cerrarlo en caso de error técnico
        if (!$this->em->isOpen()) {
            $this->em = static::getContainer()->get(EntityManagerInterface::class);
        }

        $this->em->refresh($productA);
        $this->em->refresh($productB);

        $this->assertEquals(10, $productA->getStock());
        $this->assertEquals(1, $productB->getStock());

        $this->assertCount(0, $this->em->getRepository(Sale::class)->findAll());
        $this->assertCount(0, $this->em->getRepository(LineSale::class)->findAll());
        $this->assertCount(2, $this->em->getRepository(Movement::class)->findAll()); // solo entradas
    }
}
