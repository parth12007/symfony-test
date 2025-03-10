<?php

namespace App\Service;

use App\Entity\Product;
use App\Enum\CategoryEnum;
use Symfony\Component\String\Slugger\AsciiSlugger;

class ProductService
{
    private const SKU_TITLE_LENGTH = 3;
    private const SKU_CATEGORY_LENGTH = 2;
    private const SKU_DATE_FORMAT = 'ymd';
    private const SKU_RANDOM_LENGTH = 4;
    private const SKU_PADDING_CHAR = 'X';

    public function __construct(
        private readonly AsciiSlugger $slugger = new AsciiSlugger()
    ) {
    }

    public function generateSku(Product $product): string
    {
        /** @var string|null $title */
        $title = $product->getTitle();
        /** @var CategoryEnum|null $category */
        $category = $product->getCategory();

        $titlePrefix = $this->generateTitlePrefix($title);
        $categoryPrefix = $this->generateCategoryPrefix($category);
        $timestamp = $this->generateTimestamp();
        $randomSuffix = $this->generateRandomSuffix();

        return $this->formatSku($titlePrefix, $categoryPrefix, $timestamp, $randomSuffix);
    }

    private function generateTitlePrefix(?string $title): string
    {
        /** @var string $cleanTitle */
        $cleanTitle = $this->slugger->slug($title ?? '')->upper();

        return str_pad(
            substr($cleanTitle, 0, self::SKU_TITLE_LENGTH),
            self::SKU_TITLE_LENGTH,
            self::SKU_PADDING_CHAR
        );
    }

    private function generateCategoryPrefix(?CategoryEnum $category): string
    {
        if ($category === null) {
            throw new \InvalidArgumentException('Product category cannot be null');
        }

        return strtoupper(substr($category->value, 0, self::SKU_CATEGORY_LENGTH));
    }

    private function generateTimestamp(): string
    {
        return date(self::SKU_DATE_FORMAT);
    }

    private function generateRandomSuffix(): string
    {
        return strtoupper(substr(uniqid(), -self::SKU_RANDOM_LENGTH));
    }

    private function formatSku(
        string $titlePrefix,
        string $categoryPrefix,
        string $timestamp,
        string $randomSuffix
    ): string {
        return sprintf('%s-%s-%s-%s', $titlePrefix, $categoryPrefix, $timestamp, $randomSuffix);
    }

    /**
     * Fixes an invalid SKU by ensuring proper format and length
     */
    public function fixSku(string $sku): string
    {
        $parts = explode('-', $sku);

        // If we don't have exactly 4 parts, create a new SKU
        if (count($parts) !== 4) {
            if (count($parts) === 3 && strlen($parts[0]) === 3 && strlen($parts[1]) === 2) {
                // Handle case where only random suffix is missing
                return sprintf(
                    '%s-%s-%s-%s',
                    $parts[0],
                    $parts[1],
                    $parts[2],
                    $this->generateRandomSuffix()
                );
            }

            return $this->createDefaultSku();
        }

        // Fix title prefix (preserve if valid)
        $titlePrefix = strlen($parts[0]) === self::SKU_TITLE_LENGTH
            ? strtoupper($parts[0])
            : str_pad(
                substr($parts[0], 0, self::SKU_TITLE_LENGTH),
                self::SKU_TITLE_LENGTH,
                self::SKU_PADDING_CHAR
            );

        // Fix category prefix (preserve if valid)
        $categoryPrefix = strlen($parts[1]) === self::SKU_CATEGORY_LENGTH
            ? strtoupper($parts[1])
            : str_pad(
                substr($parts[1], 0, self::SKU_CATEGORY_LENGTH),
                self::SKU_CATEGORY_LENGTH,
                'X'
            );

        // Fix timestamp
        $timestamp = preg_match('/^\d{6}$/', $parts[2])
            ? $parts[2]
            : date(self::SKU_DATE_FORMAT);

        // Fix random suffix
        $randomSuffix = preg_match('/^[A-Z0-9]{4}$/', strtoupper($parts[3]))
            ? strtoupper($parts[3])
            : $this->generateRandomSuffix();

        return $this->formatSku($titlePrefix, $categoryPrefix, $timestamp, $randomSuffix);
    }

    private function createDefaultSku(): string
    {
        return sprintf(
            '%s-%s-%s-%s',
            str_repeat(self::SKU_PADDING_CHAR, self::SKU_TITLE_LENGTH),
            str_repeat(self::SKU_PADDING_CHAR, self::SKU_CATEGORY_LENGTH),
            date(self::SKU_DATE_FORMAT),
            $this->generateRandomSuffix()
        );
    }
}
