<?php

declare(strict_types=1);

namespace App\Tenders\Domain\Entity;

use App\Tenders\Domain\ValueObject\Money;
use App\Tenders\Domain\ValueObject\DateRange;
use App\Tenders\Domain\Event\TenderCreated;
use App\Tenders\Domain\Event\TenderUpdated;

final class Tender
{
    /** @var object[] */
    private array $domainEvents = [];

    private function __construct(
        private readonly TenderId $id,
        private string $title,
        private string $description,
        private string $categoryId,
        private Money $budget,
        private string $region,
        private DateRange $deadline,
        private string $sourceId,
        private TenderStatus $status,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(
        TenderId $id,
        string $title,
        string $description,
        string $categoryId,
        Money $budget,
        string $region,
        DateRange $deadline,
        string $sourceId,
    ): self {
        $tender = new self(
            id: $id,
            title: $title,
            description: $description,
            categoryId: $categoryId,
            budget: $budget,
            region: $region,
            deadline: $deadline,
            sourceId: $sourceId,
            status: TenderStatus::Active,
            createdAt: new \DateTimeImmutable(),
        );

        $tender->domainEvents[] = new TenderCreated($id->toString());

        return $tender;
    }

    public static function restore(
        TenderId $id,
        string $title,
        string $description,
        string $categoryId,
        Money $budget,
        string $region,
        DateRange $deadline,
        string $sourceId,
        TenderStatus $status,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            title: $title,
            description: $description,
            categoryId: $categoryId,
            budget: $budget,
            region: $region,
            deadline: $deadline,
            sourceId: $sourceId,
            status: $status,
            createdAt: $createdAt,
        );
    }

    public function updateBudget(Money $budget): void
    {
        $this->budget = $budget;
        $this->domainEvents[] = new TenderUpdated($this->id->toString(), ['budget']);
    }

    public function close(): void
    {
        $this->status = TenderStatus::Closed;
    }

    public function getId(): TenderId
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    public function getBudget(): Money
    {
        return $this->budget;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function getDeadline(): DateRange
    {
        return $this->deadline;
    }

    public function getSourceId(): string
    {
        return $this->sourceId;
    }

    public function getStatus(): TenderStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return object[] */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
