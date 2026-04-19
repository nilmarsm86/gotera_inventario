<?php

namespace App\Tests\Integration\Service;

use App\Entity\Product;
use App\Service\InventoryService;
use App\ValueObject\Money;
use App\ValueObject\Quantity;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InventoryConcurrencyTest extends KernelTestCase
{
    private EntityManagerInterface $em1;
    private EntityManagerInterface $em2;
    private InventoryService $service1;
    private InventoryService $service2;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        // Dos entity managers separados (simulan dos usuarios)
        $this->em1 = $container->get('doctrine')->getManager();
        $this->em2 = $container->get('doctrine')->getManager();

        $this->service1 = new InventoryService($this->em1);
        $this->service2 = new InventoryService($this->em2);

        // limpiar BD entre tests (clave)
        $this->truncateEntities();
    }

    private function truncateEntities(): void
    {
        $schemaTool = new SchemaTool($this->em1);
        $metadata = $this->em1->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testConcurrentDeparture(): void
    {
        // Crear producto con stock inicial
        $product = new Product('Test', 'SKU-CONC', new Money(1000));
        $this->em1->persist($product);
        $this->em1->flush();

        $this->service1->registerEntry($product, new Quantity(10));

        // Recargar para tener versión actualizada
        $this->em1->refresh($product);
        $currentVersion = $product->getVersion(); // versión actual en BD

        // Simular proceso B que leyó ANTES del registerEntry (versión vieja)
        $staleProduct = $this->em1->find(Product::class, $product->getId());

        // Proceso A modifica → versión sube en BD
        $this->service1->registerEntry($product, new Quantity(5));

        // Forzar que staleProduct crea que tiene la versión anterior
        $this->expectException(OptimisticLockException::class);

        // Verificar lock con versión obsoleta → debe explotar
        $this->em1->lock($staleProduct, LockMode::OPTIMISTIC, $currentVersion);
        $this->em1->flush();
    }
}
