<?php

namespace App\Entity;

use App\Enum\CategoryEnum;
use App\Repository\ProductRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('sku')]
class Product
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $uuid = null;

    #[Assert\NotBlank(message: 'This value should not be blank')]
    #[ORM\Column(type: Types::STRING, nullable: false)]
    private string $title;

    #[Assert\NotBlank(message: 'This value should not be blank')]
    #[ORM\Column(type: Types::TEXT, nullable: false)]
    private string $shortDescription;

    #[Assert\NotBlank(message: 'This value should not be blank')]
    #[Assert\Positive(message: 'This value should be positive')]
    #[ORM\Column(type: Types::FLOAT, nullable: false)]
    private float $priceExclVat;

    #[Assert\NotBlank(message: 'This value should not be blank')]
    #[ORM\Column(type: Types::STRING, enumType: CategoryEnum::class, nullable: false)]
    private CategoryEnum $category;

    #[ORM\Column(length: 255, nullable: false)]
    private string $imageFilename = '';

    /**
     * @var UploadedFile|null
     */
    #[Assert\File(
        maxSize: '1024k',
        mimeTypes: ['image/jpeg', 'image/jpg', 'image/png'],
        mimeTypesMessage: 'Please upload a valid image file (JPG, JPEG or PNG)',
        maxSizeMessage: 'The file is too large ({{ size }} {{ suffix }}). Maximum allowed size is {{ limit }} {{ suffix }}.'
    )]
    private ?UploadedFile $imageFile = null;

    #[Assert\NotBlank(message: 'This value should not be blank')]
    #[Assert\Positive(message: 'This value should be positive')]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private int $quantity = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sku = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    // Getters and setters
    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getPriceExclVat(): ?float
    {
        return $this->priceExclVat;
    }

    public function setPriceExclVat(float $priceExclVat): static
    {
        $this->priceExclVat = $priceExclVat;

        return $this;
    }

    public function getCategory(): ?CategoryEnum
    {
        return $this->category;
    }

    public function setCategory(CategoryEnum $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getImageFilename(): string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(string $imageFilename): self
    {
        $this->imageFilename = $imageFilename;

        return $this;
    }

    public function getImageFile(): ?UploadedFile
    {
        return $this->imageFile;
    }

    public function setImageFile(?UploadedFile $imageFile): self
    {
        $this->imageFile = $imageFile;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function delete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    public function restore(): void
    {
        $this->deletedAt = null;
    }

    public function generateSku(): string
    {
        $title = $this->title ?? '';
        $cleanTitle = (string)preg_replace('/[^A-Za-z0-9]/', '', $title);
        $sku = strtoupper((string)substr($cleanTitle, 0, 6));
        $randomString = (string)substr(str_shuffle('0123456789'), 0, 4);

        return $sku . $randomString;
    }
}
