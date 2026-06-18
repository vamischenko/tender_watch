<?php

declare(strict_types=1);

namespace App\Tenders\Infrastructure\Persistence;

use App\Tenders\Domain\Entity\Tender;
use App\Tenders\Domain\Entity\TenderId;
use App\Tenders\Domain\Entity\TenderStatus;
use App\Tenders\Domain\Repository\CategoryRepositoryInterface;
use App\Tenders\Domain\Repository\TenderCollection;
use App\Tenders\Domain\Repository\TenderFilter;
use App\Tenders\Domain\Repository\TenderRepositoryInterface;
use App\Tenders\Domain\ValueObject\DateRange;
use App\Tenders\Domain\ValueObject\Money;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final class DbTenderRepository implements TenderRepositoryInterface
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function findById(TenderId $id): ?Tender
    {
        $row = $this->db->createCommand(
            'SELECT * FROM tenders WHERE id = :id LIMIT 1'
        )->bindValue(':id', $id->toString())->queryOne();

        return $row ? $this->hydrate($row) : null;
    }

    public function findAll(TenderFilter $filter, int $page, int $perPage): TenderCollection
    {
        $conditions = ['1=1'];
        $params = [];

        if ($filter->activeOnly) {
            $conditions[] = "status = 'active'";
        }
        if ($filter->categoryId) {
            $conditions[] = 'category_id = :category_id';
            $params[':category_id'] = $filter->categoryId;
        }
        if ($filter->region) {
            $conditions[] = 'region = :region';
            $params[':region'] = $filter->region;
        }
        if ($filter->minBudget !== null) {
            $conditions[] = 'budget_amount >= :min_budget';
            $params[':min_budget'] = $filter->minBudget;
        }
        if ($filter->maxBudget !== null) {
            $conditions[] = 'budget_amount <= :max_budget';
            $params[':max_budget'] = $filter->maxBudget;
        }
        if ($filter->query) {
            $conditions[] = "(title ILIKE :q OR description ILIKE :q)";
            $params[':q'] = '%' . $filter->query . '%';
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $total = (int)$this->db->createCommand(
            "SELECT COUNT(*) FROM tenders WHERE {$where}",
            $params
        )->queryScalar();

        $rows = $this->db->createCommand(
            "SELECT * FROM tenders WHERE {$where} ORDER BY created_at DESC LIMIT :limit OFFSET :offset",
            array_merge($params, [':limit' => $perPage, ':offset' => $offset])
        )->queryAll();

        return new TenderCollection(
            items: array_map($this->hydrate(...), $rows),
            total: $total,
            page: $page,
            perPage: $perPage,
        );
    }

    public function save(Tender $tender): void
    {
        $this->db->createCommand()->upsert('tenders', [
            'id' => $tender->getId()->toString(),
            'title' => $tender->getTitle(),
            'description' => $tender->getDescription(),
            'category_id' => $tender->getCategoryId(),
            'budget_amount' => $tender->getBudget()->getAmount(),
            'budget_currency' => $tender->getBudget()->getCurrency(),
            'region' => $tender->getRegion(),
            'deadline_at' => $tender->getDeadline()->getDeadlineAt()->format('Y-m-d H:i:s'),
            'published_at' => $tender->getDeadline()->getPublishedAt()->format('Y-m-d H:i:s'),
            'source_id' => $tender->getSourceId(),
            'status' => $tender->getStatus()->value,
            'created_at' => $tender->getCreatedAt()->format('Y-m-d H:i:s'),
        ], true)->execute();

        foreach ($tender->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }

    public function existsBySourceId(string $sourceId): bool
    {
        return (bool)$this->db->createCommand(
            'SELECT 1 FROM tenders WHERE source_id = :source_id LIMIT 1'
        )->bindValue(':source_id', $sourceId)->queryScalar();
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Tender
    {
        return Tender::restore(
            id: TenderId::fromString($row['id']),
            title: $row['title'],
            description: $row['description'],
            categoryId: $row['category_id'],
            budget: new Money((int)$row['budget_amount'], $row['budget_currency']),
            region: $row['region'],
            deadline: new DateRange(
                new \DateTimeImmutable($row['published_at']),
                new \DateTimeImmutable($row['deadline_at']),
            ),
            sourceId: $row['source_id'],
            status: TenderStatus::from($row['status']),
            createdAt: new \DateTimeImmutable($row['created_at']),
        );
    }
}
