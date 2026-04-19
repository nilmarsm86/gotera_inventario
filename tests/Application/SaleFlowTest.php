<?php

namespace App\Tests\Application;

use App\Entity\LineSale;
use App\Entity\Movement;
use App\Entity\Product;
use App\Service\InventoryService;
use App\Service\PricingService;
use App\ValueObject\Money;
use App\ValueObject\Quantity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SaleFlowTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private InventoryService $inventoryService;
    private PricingService $priceService;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
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

    /**
     * @throws ORMException
     */
    public function testCompleteFlowWithChangePrice(): void
    {
        // 1. Crear producto
        $product = new Product('Producto Test', 'SKU-FLOW-1', new Money(900));

        $this->em->persist($product);
        $this->em->flush();

        // 3. Añadir stock
        $this->inventoryService->registerEntry($product, new Quantity(10));

        $movements = $this->em->getRepository(Movement::class)->findAll();
        $this->assertEquals(9000, $movements[0]->getTotal()->getValue());

        // 2. Definir precio inicial (10€)
        $this->priceService->changePrice($product, new Money(1000));

        $this->em->refresh($product);
        $this->assertEquals(10, $product->getStock());

        // 4. Cambiar precio a 15€
        $this->priceService->changePrice($product, new Money(1500));

        $this->em->refresh($product);
        $this->assertEquals(1500, $product->getPrice()->getValue());

        // 5. Crear venta (2 unidades)
        $actualPrice = $product->getPrice();

        // TODO: un solo servicio de aplicación que haga ambas cosas en una transacción
        $lineSale = new LineSale($product, new Quantity(2), $actualPrice);
        $this->em->persist($lineSale);
        // 6. Registrar salida de inventario
        $this->inventoryService->registerDeparture($product, new Quantity(2));

        $this->em->flush();

        // 7. Verificar stock
        $this->em->refresh($product);
        $this->assertEquals(8, $product->getStock());

        // 8. Verificar datos de la venta
        $this->assertEquals(1500, $lineSale->getUnitPrice()->getValue());
        $this->assertEquals(3000, $lineSale->getTotal()->getValue());

        // 9. Cambiar precio otra vez (20€)
        $this->priceService->changePrice($product, new Money(2000));

        // 10. Verificar que la venta NO cambia
        $this->assertEquals(1500, $lineSale->getUnitPrice()->getValue());
        $this->assertEquals(3000, $lineSale->getTotal()->getValue());

        // 11. Verificar que existe movimiento
        $movements = $this->em
            ->getRepository(Movement::class)
            ->findAll();

        $this->assertCount(2, $movements); // 1 entrada + 1 salida
    }
}
