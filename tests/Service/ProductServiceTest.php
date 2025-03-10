<?php

namespace App\Tests\Service;

use App\Entity\Product;
use App\Enum\CategoryEnum;
use App\Service\ProductService;
use PHPUnit\Framework\TestCase;

class ProductServiceTest extends TestCase
{
    private ProductService $productService;

    protected function setUp(): void
    {
        $this->productService = new ProductService();
    }

    /**
     * @dataProvider skuGenerationDataProvider
     */
    public function testGenerateSku(string $title, CategoryEnum $category, string $expectedPattern): void
    {
        /** @var Product $product */
        $product = new Product();
        $product->setTitle($title);
        $product->setCategory($category);

        $sku = $this->productService->generateSku($product);

        $this->assertMatchesRegularExpression($expectedPattern, $sku);
        $this->assertEquals(18, strlen($sku));

        $parts = explode('-', $sku);
        $this->assertCount(4, $parts);

        $this->assertEquals(3, strlen($parts[0]));
        $this->assertEquals(2, strlen($parts[1]));
        $this->assertEquals(6, strlen($parts[2]));
        $this->assertTrue(is_numeric($parts[2]));
        $this->assertEquals(4, strlen($parts[3]));
    }

    /**
     * @return array<string, array{string, CategoryEnum, string}>
     */
    public function skuGenerationDataProvider(): array
    {
        return [
            'normal product' => [
                'Gym Bag',
                CategoryEnum::SPORTSFITNESS,
                '/^GYM-SP-\d{6}-[A-Z0-9]{4}$/',
            ],
        ];
    }

    public function testSkuUniqueness(): void
    {
        /** @var Product $product */
        $product = new Product();
        $product->setTitle('Test Product');
        $product->setCategory(CategoryEnum::SPORTSFITNESS);

        $sku1 = $this->productService->generateSku($product);
        sleep(1);
        $sku2 = $this->productService->generateSku($product);

        $this->assertNotEquals($sku1, $sku2, 'SKUs should be unique even for the same product');
    }

    public function testSkuFixer(): void
    {
        $testCases = [
            // Missing random suffix
            'ABC-EL-230215' => [
                'input' => 'ABC-EL-230215',
                'pattern' => '/^ABC-EL-230215-[A-Z0-9]{4}$/',
            ],
            // Short title prefix
            'AB-EL-230215-1234' => [
                'input' => 'AB-EL-230215-1234',
                'pattern' => '/^ABX-EL-230215-1234$/',
            ],
            // Short category
            'ABC-E-230215-1234' => [
                'input' => 'ABC-E-230215-1234',
                'pattern' => '/^ABC-EX-230215-1234$/',
            ],
            // Invalid date
            'ABC-EL-INVALID-1234' => [
                'input' => 'ABC-EL-INVALID-1234',
                'pattern' => '/^ABC-EL-\d{6}-1234$/',
            ],
            // Short random suffix
            'ABC-EL-230215-12' => [
                'input' => 'ABC-EL-230215-12',
                'pattern' => '/^ABC-EL-230215-[A-Z0-9]{4}$/',
            ],
            // Completely invalid
            'invalid-sku' => [
                'input' => 'invalid-sku',
                'pattern' => '/^XXX-XX-\d{6}-[A-Z0-9]{4}$/',
            ],
            // Lowercase parts
            'abc-el-230215-1234' => [
                'input' => 'abc-el-230215-1234',
                'pattern' => '/^ABC-EL-230215-1234$/',
            ],
        ];

        foreach ($testCases as $case => $data) {
            $fixedSku = $this->productService->fixSku($data['input']);
            $this->assertMatchesRegularExpression(
                $data['pattern'],
                $fixedSku,
                sprintf('Failed fixing SKU: %s', $case)
            );
        }
    }
}
