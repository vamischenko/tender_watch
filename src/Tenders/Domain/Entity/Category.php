<?php

declare(strict_types=1);

namespace App\Tenders\Domain\Entity;

final class Category
{
    public function __construct(
        private readonly string $id,
        private string $name,
        private string $slug,
        private ?string $parentId,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }
}
