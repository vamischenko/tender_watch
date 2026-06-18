<?php

declare(strict_types=1);

namespace App\Tenders\Infrastructure\Persistence;

use App\Tenders\Domain\Entity\Category;
use App\Tenders\Domain\Repository\CategoryRepositoryInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final class DbCategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function findById(string $id): ?Category
    {
        $row = $this->db->createCommand(
            'SELECT * FROM categories WHERE id = :id LIMIT 1'
        )->bindValue(':id', $id)->queryOne();

        return $row ? $this->hydrate($row) : null;
    }

    public function findAll(): array
    {
        $rows = $this->db->createCommand(
            'SELECT * FROM categories ORDER BY name'
        )->queryAll();

        return array_map($this->hydrate(...), $rows);
    }

    public function save(Category $category): void
    {
        $this->db->createCommand()->upsert('categories', [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'parent_id' => $category->getParentId(),
        ], true)->execute();
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Category
    {
        return new Category(
            id: $row['id'],
            name: $row['name'],
            slug: $row['slug'],
            parentId: $row['parent_id'],
        );
    }
}
