<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Product;
use App\Enum\CategoryEnum;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Product
 */
final class ProductTest extends TestCase
{
    private Product $product;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->product = new Product();
    }

    /**
     * @return void
     */
    public function testInitialState(): void
    {
        $uuid = $this->product->getUuid();
        $this->assertNotNull($uuid, 'UUID should be automatically generated');
        $this->assertNull($this->product->getDeletedAt(), 'DeletedAt should be null by default');
        $this->assertSame(0, $this->product->getQuantity(), 'Quantity should be 0 by default');
        $this->assertNotNull($this->product->getCreatedAt(), 'CreatedAt should be automatically set');
        $this->assertNotNull($this->product->getUpdatedAt(), 'UpdatedAt should be automatically set');
    }

    /**
     * @return void
     */
    public function testProductAttributes(): void
    {
        $title = 'Test Product';
        $description = 'Test Description';
        $price = 99.99;
        $category = CategoryEnum::ELECTRONICS;
        $sku = 'TEST123';
        $quantity = 10;

        $this->product
            ->setTitle($title)
            ->setShortDescription($description)
            ->setPriceExclVat($price)
            ->setCategory($category)
            ->setSku($sku)
            ->setQuantity($quantity);

        $this->assertSame($title, $this->product->getTitle());
        $this->assertSame($description, $this->product->getShortDescription());
        $this->assertSame($price, $this->product->getPriceExclVat());
        $this->assertSame($category, $this->product->getCategory());
        $this->assertSame($sku, $this->product->getSku());
        $this->assertSame($quantity, $this->product->getQuantity());
    }

    /**
     * @return void
     */
    public function testSoftDelete(): void
    {
        $this->assertFalse($this->product->isDeleted());

        $this->product->delete();
        $this->assertTrue($this->product->isDeleted());
        $this->assertNotNull($this->product->getDeletedAt());

        $this->product->restore();
        $this->assertFalse($this->product->isDeleted());
        $this->assertNull($this->product->getDeletedAt());
    }

    /**
     * @test
     * @return void
     */
    public function testSkuGeneration(): void
    {
        $this->product->setTitle('Test Product 123');
        $sku = $this->product->generateSku();

        $this->assertMatchesRegularExpression(
            '/^TESTPR[0-9]{4}$/',
            $sku,
            'SKU should match pattern TESTPR followed by 4 digits'
        );
    }
}
