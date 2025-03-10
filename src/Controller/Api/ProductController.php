<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Enum\CategoryEnum;
use App\Repository\ProductRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/v1')]
class ProductController extends AbstractController
{
    /**
     * List all products
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns the list of products",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class))
     *     )
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="Page number",
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Products")
     */
    #[Route('/products', name: 'api_products_list', methods: ['GET'])]
    public function list(Request $request, ProductRepository $productRepository): Response
    {
        $category = $request->query->get('category');
        if ($category !== null) {
            $category = CategoryEnum::tryFrom($category);
        }

        $query = $productRepository->getSearchQuery(
            $request->query->get('q'),
            $category
        );
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $products = $query->setMaxResults($limit)
                         ->setFirstResult(($page - 1) * $limit)
                         ->getQuery()
                         ->getResult();

        return $this->json([
            'data' => array_map(fn (Product $product) => $this->serializeProduct($product), $products),
            'page' => $page,
            'limit' => $limit,
            'total' => count($query->getQuery()->getResult()),
        ]);
    }

    /**
     * Get a single product
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns a product",
     *     @Model(type=Product::class)
     * )
     * @OA\Response(
     *     response=404,
     *     description="Product not found"
     * )
     * @OA\Parameter(
     *     name="uuid",
     *     in="path",
     *     description="Product UUID",
     *     required=true,
     *     @OA\Schema(type="string", format="uuid")
     * )
     * @OA\Tag(name="Products")
     */
    #[Route('/products/{uuid}', name: 'api_products_show', methods: ['GET'])]
    public function show(string $uuid, ProductRepository $productRepository): JsonResponse
    {
        try {
            $validUuid = Uuid::fromString($uuid);
            $product = $productRepository->findByUuid($validUuid);

            if (! $product) {
                return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
            }

            return $this->json([
                'data' => $this->serializeProduct($product),
            ]);

        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Invalid UUID format'], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProduct(Product $product): array
    {
        $category = $product->getCategory();

        return [
            'uuid' => $product->getUuid(),
            'title' => $product->getTitle(),
            'sku' => $product->getSku(),
            'short_description' => $product->getShortDescription(),
            'price_excl_vat' => $product->getPriceExclVat(),
            'category' => $category ? $category->value : null,
            'quantity' => $product->getQuantity(),
            'image_url' => $product->getImageFilename()
                ? '/uploads/products/' . $product->getImageFilename()
                : null,
            'created_at' => $product->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $product->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
