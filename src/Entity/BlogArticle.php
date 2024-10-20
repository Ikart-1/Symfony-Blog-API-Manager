<?php

namespace App\Entity;

use App\Repository\BlogArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Controller\BlogArticleController;
use App\Validator\Constraints as AppAssert;

#[ORM\Entity(repositoryClass: BlogArticleRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['blog_article:read']]),
        new GetCollection(normalizationContext: ['groups' => ['blog_article:read']]),
        new Post(
            controller: BlogArticleController::class,
            deserialize: false,
            validationContext: ['groups' => ['Default']],
            inputFormats: ['multipart' => ['multipart/form-data']]
        ),
        new Patch(
            controller: BlogArticleController::class,
            deserialize: false,
            inputFormats: ['multipart' => ['multipart/form-data']]
        ),
        new Delete()
    ],
)]
class BlogArticle
{
    #[Groups(['blog_article:read', 'blog_article:write'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['blog_article:read', 'blog_article:write'])]
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank(message: 'Author ID should not be blank.')]
    #[Assert\Positive(message: 'Author ID must be a positive integer.')]
    private ?int $authorId = null;

    #[Groups(['blog_article:read', 'blog_article:write'])]
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Title should not be blank.')]
    #[Assert\Length(max: 100, maxMessage: 'Title cannot be longer than 100 characters.')]
    private ?string $title = null;

    #[Groups(['blog_article:read', 'blog_article:write'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'Publication date should not be null.')]
    private ?\DateTimeInterface $publicationDate = null;

    #[Groups(['blog_article:read', 'blog_article:write'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'Creation date should not be null.')]
    private ?\DateTimeInterface $creationDate = null;

    #[Groups(['blog_article:read', 'blog_article:write'])]
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Content should not be blank.')]
    #[AppAssert\NoBannedWords]
    private ?string $content = null;

    #[Groups(['blog_article:read', 'blog_article:write'])]
    #[ORM\Column(type: Types::JSON)]
    #[Assert\All(
        new Assert\Length(max: 50, maxMessage: 'Each keyword cannot be longer than 50 characters.')
    )]
    private array $keywords = [];

    #[Groups(['blog_article:read', 'blog_article:write'])]
    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices:['draft', 'published', 'deleted'], message: 'Status must be either draft, published, or deleted.')]
    private ?string $status = 'draft';

    #[Groups(['blog_article:read', 'blog_article:write'])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Slug should not be blank.')]
    private ?string $slug = null;

    #[Groups(['blog_article:read', 'blog_article:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverPictureRef = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getAuthorId(): ?int
    {
        return $this->authorId;
    }

    public function setAuthorId(?int $authorId): void
    {
        $this->authorId = $authorId;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getPublicationDate(): ?\DateTimeInterface
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(?\DateTimeInterface $publicationDate): void
    {
        $this->publicationDate = $publicationDate;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(?\DateTimeInterface $creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function setKeywords(array $keywords): void
    {
        $this->keywords = $keywords;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function getCoverPictureRef(): ?string
    {
        return $this->coverPictureRef;
    }

    public function setCoverPictureRef(?string $coverPictureRef): void
    {
        $this->coverPictureRef = $coverPictureRef;
    }
}
