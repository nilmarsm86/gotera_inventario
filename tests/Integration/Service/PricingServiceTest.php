<?php

namespace App\Tests\Integration\Service;

use App\Entity\Product;
use App\Entity\ProductPrice;
use App\Service\PricingService;
use App\ValueObject\Money;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PricingServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private PricingService $service;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = static::getContainer()->get(PricingService::class);

        $this->truncateEntities();
    }

    private function truncateEntities(): void
    {
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testCreateInitialPrice(): void
    {
        $product = new Product('Test', 'SKU-PRICE-1', new Money(1000));

        $this->em->persist($product);
        $this->em->flush();

        $this->service->changePrice($product, new Money(1500));

        $prices = $this->em->getRepository(ProductPrice::class)->findAll();

        $this->assertCount(2, $prices);

        $this->assertEquals(1000, $prices[0]->getPrice()->getValue());
        $this->assertNotNull($prices[0]->getEndAt());

        $this->assertEquals(1500, $prices[1]->getPrice()->getValue());
        $this->assertNull($prices[1]->getEndAt());
    }

    public function testClosePreviousPriceAndCreateNew(): void
    {
        $product = new Product('Test', 'SKU-PRICE-2', new Money(900));

        $this->em->persist($product);
        $this->em->flush();

        $this->service->changePrice($product, new Money(1000));
        $this->service->changePrice($product, new Money(2000));

        $prices = $this->em->getRepository(ProductPrice::class)->findBy(
            ['product' => $product],
            ['startAt' => 'ASC']
        );

        $this->assertCount(3, $prices);

        // Primer precio cerrado
        $this->assertNotNull($prices[0]->getEndAt());

        // Segundo precio activo
        $this->assertNull($prices[2]->getEndAt());
        $this->assertEquals(2000, $prices[2]->getPrice()->getValue());
    }

    /**
     * @throws ORMException
     */
    public function testUpdateCurrentProductPrice(): void
    {
        $product = new Product('Test', 'SKU-PRICE-3', new Money(1000));

        $this->em->persist($product);
        $this->em->flush();

        $this->service->changePrice($product, new Money(1500));

        $this->em->refresh($product);

        $this->assertEquals(1500, $product->getPrice()->getValue());
    }

    public function testDoesNotAllowNegativePrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $product = new Product('Test', 'SKU-PRICE-4', new Money(1000));

        $this->em->persist($product);
        $this->em->flush();

        $this->service->changePrice($product, new Money(-500));
    }

    public function testOnlyOneActivePriceActivo(): void
    {
        $product = new Product('Test', 'SKU-PRICE-5', new Money(1000));

        $this->em->persist($product);
        $this->em->flush();

        $this->service->changePrice($product, new Money(1000));
        $this->service->changePrice($product, new Money(2000));
        $this->service->changePrice($product, new Money(3000));

        $actives = $this->em->getRepository(ProductPrice::class)->findBy([
            'product' => $product,
            'endAt' => null,
        ]);

        $this->assertCount(1, $actives);
    }

    public function testProductoSiempreTienePrecioHistorico(): void
    {
        $product = new Product('Test', 'SKU-INIT');

        $this->em->persist($product);
        $this->em->flush();

        $this->service->changePrice($product, new Money(1000));

        $prices = $this->em->getRepository(ProductPrice::class)->findBy([
            'product' => $product,
            //            'endAt' => null
        ]);

        $this->assertCount(2, $prices);
    }
}
