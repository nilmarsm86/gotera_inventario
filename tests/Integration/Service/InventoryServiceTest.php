<?php

namespace App\Tests\Integration\Service;

use App\Entity\Movement;
use App\Entity\Product;
use App\Service\InventoryService;
use App\ValueObject\Money;
use App\ValueObject\Quantity;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InventoryServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private InventoryService $service;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->service = $container->get(InventoryService::class);

        // limpiar BD entre tests (clave)
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
    public function testInputIncreaseStockAndRecordMovement(): void
    {
        $product = new Product('Test', 'SKU1', new Money(1000));

        $this->em->persist($product);
        $this->em->flush();

        $this->service->registerEntry($product, new Quantity(10));

        $this->em->refresh($product);

        $this->assertEquals(10, $product->getStock());
    }

    /**
     * @throws ORMException
     */
    public function testDepartureReduceStock(): void
    {
        $product = new Product('Test', 'SKU2', new Money(1000));

        $this->em->persist($product);
        $this->em->flush();

        $this->service->registerEntry($product, new Quantity(10));
        $this->service->registerDeparture($product, new Quantity(4));

        $this->em->refresh($product);

        $this->assertEquals(6, $product->getStock());
    }

    public function testNotAllowDepartureWithoutStock(): void
    {
        $this->expectException(\RuntimeException::class);

        $product = new Product('Test', 'SKU3', new Money(1000));

        $this->em->persist($product);
        $this->em->flush();

        $this->service->registerDeparture($product, new Quantity(5));
    }

    /**
     * @throws ORMException
     */
    public function testAdjustStock(): void
    {
        $product = new Product('Test', 'SKU4', new Money(1000));

        $this->em->persist($product);
        $this->em->flush();

        $this->service->registerEntry($product, new Quantity(10));
        $this->service->adjustStock($product, new Quantity(3));

        $this->em->refresh($product);

        $this->assertEquals(3, $product->getStock());
    }

    public function testNoNegativeQuantityAllowed(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $product = new Product('Test', 'SKU5', new Money(1000));

        $this->em->persist($product);
        $this->em->flush();

        $this->service->registerEntry($product, new Quantity(-5));
    }

    public function testMovementCreatedAtEntrance(): void
    {
        $product = new Product('Test', 'SKU6', new Money(1000));

        $this->em->persist($product);
        $this->em->flush();

        $this->service->registerEntry($product, new Quantity(5));

        $movements = $this->em->getRepository(Movement::class)->findAll();

        $this->assertCount(1, $movements);
    }
}
