<?php

declare(strict_types=1);

namespace App\Ingestion\Infrastructure;

use App\Ingestion\Domain\RawTenderDTO;
use App\Ingestion\Domain\SourceConnectorInterface;

final class FakeTenderConnector implements SourceConnectorInterface
{
    private const CATEGORIES = [
        'Строительство', 'IT и телекоммуникации', 'Медицина', 'Образование',
        'Транспорт', 'Энергетика', 'Продовольствие', 'Безопасность',
    ];

    private const REGIONS = [
        'Москва', 'Санкт-Петербург', 'Новосибирск', 'Екатеринбург',
        'Казань', 'Нижний Новгород', 'Самара', 'Уфа', 'Красноярск',
    ];

    private const TITLES = [
        'Поставка компьютерного оборудования',
        'Ремонт дорог и тротуаров',
        'Строительство спортивного комплекса',
        'Разработка программного обеспечения',
        'Поставка медицинских расходных материалов',
        'Техническое обслуживание лифтов',
        'Охрана объектов',
        'Поставка продуктов питания',
        'Капитальный ремонт здания',
        'Услуги связи и интернет',
    ];

    public function __construct(private readonly int $totalItems = 100)
    {
    }

    public function getType(): string
    {
        return 'fake';
    }

    public function fetch(int $page, int $pageSize): array
    {
        $offset = ($page - 1) * $pageSize;
        $items = [];

        for ($i = $offset; $i < min($offset + $pageSize, $this->totalItems); $i++) {
            $publishedAt = new \DateTimeImmutable('-' . rand(1, 30) . ' days');
            $deadlineAt = new \DateTimeImmutable('+' . rand(10, 60) . ' days');

            $items[] = new RawTenderDTO(
                externalId: 'fake-' . ($i + 1),
                title: self::TITLES[$i % count(self::TITLES)] . ' №' . ($i + 1),
                description: 'Описание тендера №' . ($i + 1)
                    . '. Требования к поставщику: наличие лицензии, опыт работы не менее 3 лет.',
                categoryName: self::CATEGORIES[$i % count(self::CATEGORIES)],
                budgetAmount: rand(100_000, 50_000_000),
                budgetCurrency: 'RUB',
                region: self::REGIONS[$i % count(self::REGIONS)],
                publishedAt: $publishedAt,
                deadlineAt: $deadlineAt,
                sourceType: 'fake',
            );
        }

        return $items;
    }

    public function getTotalPages(int $pageSize): int
    {
        return (int)ceil($this->totalItems / $pageSize);
    }
}
