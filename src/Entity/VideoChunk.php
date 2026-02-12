<?php

namespace App\Entity;

use App\Repository\VideoChunkRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: VideoChunkRepository::class)]
#[ORM\Table(name: '`video_chunk`')]
class VideoChunk
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'uuid')]
    private ?Uuid $uploadSessionId = null;

    #[ORM\Column(type: 'integer')]
    private ?int $chunkNumber = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $filePath = null;

    #[ORM\Column(type: 'integer')]
    private ?int $fileSize = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $uploadedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isLast = false;

    #[ORM\Column(type: 'boolean')]
    private bool $processed = false;

    public function __construct()
    {
        $this->uploadedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUploadSessionId(): ?Uuid
    {
        return $this->uploadSessionId;
    }

    public function setUploadSessionId(Uuid $uploadSessionId): static
    {
        $this->uploadSessionId = $uploadSessionId;

        return $this;
    }

    public function getChunkNumber(): ?int
    {
        return $this->chunkNumber;
    }

    public function setChunkNumber(int $chunkNumber): static
    {
        $this->chunkNumber = $chunkNumber;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): static
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function getUploadedAt(): ?\DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeImmutable $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isLast(): bool
    {
        return $this->isLast;
    }

    public function setIsLast(bool $isLast): static
    {
        $this->isLast = $isLast;

        return $this;
    }

    public function isProcessed(): bool
    {
        return $this->processed;
    }

    public function setProcessed(bool $processed): static
    {
        $this->processed = $processed;

        return $this;
    }
}
