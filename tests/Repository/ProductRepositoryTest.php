<?php

namespace App\Tests\Repository;

use App\Entity\Product;
use App\Enum\CategoryEnum;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->entityManager = $entityManager;

        /** @var ProductRepository $repository */
        $repository = $this->entityManager->getRepository(Product::class);
        $this->repository = $repository;

        // Ensure properties are initialized
        self::assertInstanceOf(EntityManagerInterface::class, $this->entityManager);
        self::assertInstanceOf(ProductRepository::class, $this->repository);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Close entity manager
        $this->entityManager->close();

        // Unset properties to help garbage collection
        unset($this->entityManager);
        unset($this->repository);
    }

    public function testSearchQuery(): void
    {
        // Create a test product
        $product = new Product();
        $product->setTitle('Test Product')
            ->setShortDescription('Test Description')
            ->setPriceExclVat(54.99)
            ->setCategory(CategoryEnum::TOYS)
            ->setSku('TEST123')
            ->setQuantity(10);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        try {
            // Test search by title
            $query = $this->repository->getSearchQuery('Test Product');
            $result = $query->getQuery()->getResult();
            self::assertCount(1, $result);
            self::assertEquals('Test Product', $result[0]->getTitle());

            // Test search by SKU
            $query = $this->repository->getSearchQuery('TEST123');
            $result = $query->getQuery()->getResult();
            self::assertCount(1, $result);
            self::assertEquals('TEST123', $result[0]->getSku());

            // Test search by category
            $query = $this->repository->getSearchQuery(null, CategoryEnum::TOYS);
            $result = $query->getQuery()->getResult();
            self::assertCount(1, $result);
            self::assertEquals(CategoryEnum::TOYS, $result[0]->getCategory());
        } finally {
            // Clean up
            $this->entityManager->remove($product);
            $this->entityManager->flush();
        }
    }

    public function testSoftDelete(): void
    {
        self::assertNotNull($this->entityManager);
        self::assertNotNull($this->repository);

        // Create a test product
        $product = new Product();
        $product->setTitle('Test Product')
            ->setShortDescription('Test Description')
            ->setPriceExclVat(54.99)
            ->setCategory(CategoryEnum::TOYS)
            ->setQuantity(10);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        // Test soft delete
        $this->repository->softDelete($product);
        $this->assertTrue($product->isDeleted());
        $this->assertNotNull($product->getDeletedAt());

        // Test restore
        $this->repository->restore($product);
        $this->assertFalse($product->isDeleted());
        $this->assertNull($product->getDeletedAt());

        // Clean up
        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }
}
