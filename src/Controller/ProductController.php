<?php

namespace App\Controller;

use App\Entity\Product;
use App\Enum\CategoryEnum;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\ProductService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Uuid;

#[Route('/products')]
class ProductController extends AbstractController
{
    public function __construct(
        private ProductService $productService
    ) {
    }

    #[Route('/', name: 'app_product_list', methods: ['GET'])]
    public function list(
        Request $request,
        ProductRepository $productRepository,
        PaginatorInterface $paginator
    ): Response {
        $search = $request->query->get('search');
        $category = $request->query->get('category');
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');
        $sortBy = $request->query->get('sort_by', 'createdAt');
        $sortOrder = $request->query->get('sort_order', 'DESC');

        $selectedCategory = null;
        if ($category) {
            try {
                $selectedCategory = CategoryEnum::from($category);
            } catch (\ValueError $e) {
                $this->addFlash('error', 'Invalid category');

                return $this->redirectToRoute('app_product_list');
            }
        }

        $queryBuilder = $productRepository->getSearchQuery(
            $search,
            $selectedCategory,
            $minPrice ? (float) $minPrice : null,
            $maxPrice ? (float) $maxPrice : null,
            $sortBy,
            $sortOrder
        );

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        $priceRange = $productRepository->getMinMaxPrices();

        return $this->render('product/list.html.twig', [
            'pagination' => $pagination,
            'categories' => CategoryEnum::cases(),
            'selectedCategory' => $selectedCategory,
            'search' => $search,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'priceRange' => $priceRange,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        ProductRepository $productRepository,
        SluggerInterface $slugger
    ): Response {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Generate SKU using the service
                $sku = $this->productService->generateSku($product);
                $product->setSku($sku);

                /** @var UploadedFile|null $imageFile */
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile instanceof UploadedFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('products_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        throw new \RuntimeException('Failed to upload image: ' . $e->getMessage());
                    }
                    $product->setImageFilename($newFilename);
                }

                $productRepository->save($product, true);
                $this->addFlash('success', 'Product created successfully');

                return $this->redirectToRoute('app_product_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('product/new.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    #[Route('/{uuid}', name: 'app_product_show', methods: ['GET'])]
    public function show(?string $uuid, ProductRepository $productRepository): Response
    {
        if (! $uuid) {
            $this->addFlash('error', 'No product identifier provided.');

            return $this->redirectToRoute('app_product_list');
        }

        try {
            $validUuid = Uuid::fromString($uuid);
            $product = $productRepository->findByUuid($validUuid);

            if (! $product instanceof Product) {
                $this->addFlash('error', 'No product found.');

                return $this->redirectToRoute('app_product_list');
            }

            return $this->render('product/show.html.twig', [
                'product' => $product,
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Invalid product identifier.');

            return $this->redirectToRoute('app_product_list');
        }
    }

    #[Route('/{uuid}/delete', name: 'app_product_delete', methods: ['POST'])]
    public function delete(
        string $uuid,
        Request $request,
        ProductRepository $productRepository
    ): Response {
        $token = (string) $request->request->get('_token');
        if (! $this->isCsrfTokenValid('delete-product', $token)) {
            $this->addFlash('error', 'Invalid CSRF token');

            return $this->redirectToRoute('app_product_list');
        }

        try {
            $validUuid = Uuid::fromString($uuid);
            /** @var Product $product */
            $product = $productRepository->findByUuid($validUuid);

            if (! $product instanceof Product) {
                $this->addFlash('error', 'Product not found');

                return $this->redirectToRoute('app_product_list');
            }

            // Delete product image if exists
            if ($product->getImageFilename()) {
                $imagePath = $this->getParameter('products_directory') . '/' . $product->getImageFilename();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // Soft delete the product
            $productRepository->softDelete($product);
            $this->addFlash('success', 'Product deleted successfully');

            return $this->redirectToRoute('app_product_list');

        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Invalid product identifier');

            return $this->redirectToRoute('app_product_list');
        }
    }
}
