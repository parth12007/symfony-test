<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Enum\CategoryEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductControllerTest extends KernelTestCase
{
    private string $testUploadDir;

    private EntityManagerInterface $entityManager;

    private ValidatorInterface $validator;

    private ?string $tempImagePath = null;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        // Get the entity manager and validator
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->validator = static::getContainer()->get(ValidatorInterface::class);

        // Get the test upload directory
        $this->testUploadDir = static::getContainer()->getParameter('product_image_directory');

        // Create upload directory if it doesn't exist
        if (! file_exists($this->testUploadDir)) {
            mkdir($this->testUploadDir, 0777, true);
        }
    }

    private function createTestImage(): void
    {
        // Create a 100x100 image
        $image = imagecreatetruecolor(100, 100);

        // Add some content to the image
        $bgColor = imagecolorallocate($image, 255, 255, 255); // White background
        if ($bgColor === false) {
            throw new \RuntimeException('Failed to allocate background color');
        }

        $textColor = imagecolorallocate($image, 0, 0, 0); // Black text
        if ($textColor === false) {
            throw new \RuntimeException('Failed to allocate text color');
        }

        // Fill the background
        imagefilledrectangle($image, 0, 0, 100, 100, $bgColor);

        // Add some text
        imagestring($image, 5, 10, 40, 'Test', $textColor);

        // Set the temp file path
        $this->tempImagePath = sys_get_temp_dir() . '/test_' . uniqid() . '.jpg';

        // Save the image
        imagejpeg($image, $this->tempImagePath);

        // Free memory
        imagedestroy($image);

        if (! file_exists($this->tempImagePath)) {
            throw new \RuntimeException('Failed to create test image');
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test database
        $this->entityManager->createQuery('DELETE FROM App\Entity\Product')->execute();
        $this->entityManager->clear();

        // Clean up test upload directory
        if (file_exists($this->testUploadDir)) {
            $files = glob($this->testUploadDir.'/*.*');
            if (is_array($files)) {
                array_map(static function (string $file): void {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }, $files);
            }
            rmdir($this->testUploadDir);
        }

        // Clean up temporary test image
        if ($this->tempImagePath && file_exists($this->tempImagePath)) {
            unlink($this->tempImagePath);
        }
    }

    public function testCreateProduct(): void
    {
        // Create test image
        $this->createTestImage();

        // Ensure we have a valid path and store it in a new variable
        if (! is_string($this->tempImagePath) || ! file_exists($this->tempImagePath)) {
            throw new \RuntimeException('Test image file not found');
        }
        $sourcePath = $this->tempImagePath; // Now PHPStan knows this is definitely a string

        if (! is_string($this->testUploadDir)) {
            throw new \RuntimeException('Upload directory not configured');
        }
        $uploadDir = $this->testUploadDir; // Now PHPStan knows this is definitely a string

        // Create UploadedFile instance
        $imageFile = new UploadedFile(
            $sourcePath,
            'test_image.jpg',
            'image/jpeg',
            null,
            true
        );

        // Create new product
        $product = new Product();
        $product->setTitle('Test Product');
        $product->setSku('TEST-123');
        $product->setShortDescription('Test product description');
        $product->setPriceExclVat(159.90);
        $product->setCategory(CategoryEnum::BEAUTY);
        $product->setQuantity(10);

        // Set the image file for validation
        $product->setImageFile($imageFile);

        // Validate the product
        $violations = $this->validator->validate($product);
        $this->assertCount(0, $violations, sprintf(
            'Product should be valid. Violations: %s',
            implode(', ', array_map(
                fn ($v) => sprintf('%s: %s', $v->getPropertyPath(), $v->getMessage()),
                iterator_to_array($violations)
            ))
        ));

        // Generate filename and handle file upload
        $newFilename = uniqid().'.jpg';
        $targetPath = $uploadDir . '/' . $newFilename;

        if (! copy($sourcePath, $targetPath)) {
            throw new \RuntimeException('Failed to copy test image file');
        }

        $product->setImageFilename($newFilename);

        // Persist and flush
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        // Get the product from database and assert
        $savedProduct = $this->entityManager->getRepository(Product::class)->findOneBy(['sku' => 'TEST-123']);

        $this->assertInstanceOf(Product::class, $savedProduct);
        $this->assertEquals('Test Product', $savedProduct->getTitle());
        $this->assertEquals('TEST-123', $savedProduct->getSku());
        $this->assertEquals('Test product description', $savedProduct->getShortDescription());
        $this->assertEquals(159.90, $savedProduct->getPriceExclVat());
        $this->assertEquals(CategoryEnum::BEAUTY, $savedProduct->getCategory());
        $this->assertEquals(10, $savedProduct->getQuantity());
        $this->assertEquals($newFilename, $savedProduct->getImageFilename());
    }

    public function testCreateInvalidProduct(): void
    {
        // Create product with invalid data
        $product = new Product();
        $product->setTitle('');
        $product->setSku('TEST-123');
        $product->setShortDescription('');
        $product->setPriceExclVat(-10);
        $product->setQuantity(-5);

        // Validate the product
        $violations = $this->validator->validate($product);

        // Assert that we have violations
        $this->assertCount(5, $violations, 'Should have 5 validation violations');

        // Convert violations to array for easier testing
        $violationMessages = [];
        foreach ($violations as $violation) {
            /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
            $propertyPath = $violation->getPropertyPath();
            if ($propertyPath) {
                $violationMessages[$propertyPath] = $violation->getMessage();
            }
        }

        // Assert specific validation messages
        $this->assertArrayHasKey('title', $violationMessages);
        $this->assertArrayHasKey('shortDescription', $violationMessages);
        $this->assertArrayHasKey('priceExclVat', $violationMessages);
        $this->assertArrayHasKey('quantity', $violationMessages);
        $this->assertArrayHasKey('category', $violationMessages);

        $this->assertEquals('This value should not be blank', $violationMessages['title']);
        $this->assertEquals('This value should not be blank', $violationMessages['shortDescription']);
        $this->assertEquals('This value should be positive', $violationMessages['priceExclVat']);
        $this->assertEquals('This value should be positive', $violationMessages['quantity']);
        $this->assertEquals('This value should not be blank', $violationMessages['category']);
    }

    public function testCreateProductWithInvalidCategory(): void
    {
        $product = new Product();
        $product->setTitle('Test Product');
        $product->setSku('TEST-123');
        $product->setShortDescription('Test description');
        $product->setPriceExclVat(159.90);
        $product->setQuantity(10);

        $violations = $this->validator->validate($product);

        $this->assertCount(1, $violations, 'Should have 1 validation violations');

        $violationMessages = [];
        foreach ($violations as $violation) {
            /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
            $propertyPath = $violation->getPropertyPath();
            if ($propertyPath) {
                $violationMessages[$propertyPath] = $violation->getMessage();
            }
        }

        $this->assertArrayHasKey('category', $violationMessages, 'Should have category violation');
        $this->assertEquals('This value should not be blank', $violationMessages['category']);
    }
}
