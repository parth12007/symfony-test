<?php

namespace App\Repository;

use App\Entity\Product;
use App\Enum\CategoryEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findByUuid(Uuid $uuid): ?Product
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    public function save(Product $product, bool $flush = false): void
    {
        try {
            $this->getEntityManager()->persist($product);

            if ($flush) {
                $this->getEntityManager()->flush();
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to save product: ' . $e->getMessage());
        }
    }

    public function remove(Product $product, bool $flush = false): void
    {
        $this->getEntityManager()->remove($product);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<int, Product>
     */
    public function search(?string $query = null, ?CategoryEnum $category = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.deletedAt IS NULL');

        if ($query) {
            $qb->andWhere('p.title LIKE :query OR p.sku LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($category) {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $category);
        }

        return $qb->orderBy('p.createdAt', 'DESC')
                 ->getQuery()
                 ->getResult();
    }

    public function getSearchQuery(
        ?string $query = null,
        ?CategoryEnum $category = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        string $sortBy = 'createdAt',
        string $sortOrder = 'DESC'
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('p')
            ->where('p.deletedAt IS NULL');

        // Search by title or SKU
        if ($query) {
            $qb->andWhere('p.title LIKE :query OR p.sku LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        // Filter by category
        if ($category) {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $category);
        }

        // Filter by price range
        if ($minPrice !== null) {
            $qb->andWhere('p.priceExclVat >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('p.priceExclVat <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        // Sorting
        $allowedSortFields = ['title', 'priceExclVat', 'createdAt'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'createdAt';
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $qb->orderBy('p.' . $sortBy, $sortOrder);

        return $qb;
    }

    /**
     * @return array{min: float|null, max: float|null}
     */
    public function getMinMaxPrices(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('MIN(p.priceExclVat) as min', 'MAX(p.priceExclVat) as max')
            ->where('p.deletedAt IS NULL')
            ->getQuery()
            ->getOneOrNullResult();

        return [
            'min' => $result['min'] ?? null,
            'max' => $result['max'] ?? null,
        ];
    }

    public function softDelete(Product $product): void
    {
        $product->delete();
        $this->getEntityManager()->flush();
    }

    public function restore(Product $product): void
    {
        $product->restore();
        $this->getEntityManager()->flush();
    }

    /**
     * @return array<int, Product>
     */
    public function findDeletedProducts(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.deletedAt IS NOT NULL')
            ->orderBy('p.deletedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.deletedAt IS NULL')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
