# TenderWatch

Агрегатор тендеров с подписками и алертами. PHP 8.3 / Yii3 / PostgreSQL / Redis / RabbitMQ.

## Архитектура

```text
┌──────────────────────────────────────────────────────────────┐
│                         HTTP Layer                           │
│  nginx:8080 → PHP-FPM → Yii3 Router → PSR-15 Middleware     │
│  ApiKeyAuth → RateLimit → ErrorHandling → Controller         │
└──────────────────────────┬───────────────────────────────────┘
                           │
┌──────────────────────────▼───────────────────────────────────┐
│                  Application Layer (Use Cases)               │
│                                                              │
│  Tenders           Subscriptions        Identity             │
│  ─────────         ─────────────        ────────             │
│  TenderController  SubscriptionCtrl     AuthController       │
│  TenderFilter      CreateSubCmd         ApiToken             │
│  TenderCollection  UpdateSubCmd                              │
└──────────────────────────┬───────────────────────────────────┘
                           │  PSR-14 Domain Events
┌──────────────────────────▼───────────────────────────────────┐
│                    Domain Layer                               │
│                                                              │
│  Tender (Aggregate)    Subscription (Aggregate)              │
│  TenderId / Money      FilterCriteria                        │
│  DateRange / Status    SubscriptionMatched event             │
│  TenderCreated event                                         │
│                                                              │
│  MatchingEngine ──── Specification Pattern ────►             │
│    CategorySpec + BudgetSpec + RegionSpec + KeywordSpec      │
└──────────────────────────┬───────────────────────────────────┘
                           │
┌──────────────────────────▼───────────────────────────────────┐
│                 Infrastructure Layer                          │
│                                                              │
│  DbTenderRepository      RabbitMQPublisher/Consumer          │
│  (yiisoft/db-pgsql)      (php-amqplib)                       │
│                                                              │
│  EmailChannel            TelegramChannel                     │
│  (symfony/mailer)        (GuzzleHttp)                        │
│                                                              │
│  RedisCache              ZakupkiGovConnector                 │
│  (yiisoft/cache-redis)   FakeTenderConnector                 │
└──────────────────────────────────────────────────────────────┘
```

### Bounded Contexts (DDD)

| Контекст       | Назначение                                      |
| -------------- | ----------------------------------------------- |
| `Tenders`      | Агрегат Tender, репозиторий, CRUD API           |
| `Subscriptions`| Подписки пользователей с FilterCriteria         |
| `Matching`     | MatchingEngine со Specification Pattern         |
| `Notifications`| Каналы (email/telegram), очередь RabbitMQ       |
| `Ingestion`    | Загрузка тендеров из внешних источников         |
| `Identity`     | ApiToken, пользователи, аутентификация          |

### Event Flow

```text
Tender::create()
  → TenderCreated event
    → RunMatchingOnTenderCreated (listener)
      → MatchingEngine.match()
        → SubscriptionMatched events
          → EnqueueNotificationOnMatch (listener)
            → RabbitMQ: "notifications" queue
              → NotificationWorkerCommand (consumer)
                → EmailChannel / TelegramChannel
```

## Запуск

### Требования

- Docker 24+ и Docker Compose v2
- Git

### Быстрый старт

```bash
git clone <repo>
cd tender_watch

# Поднять инфраструктуру
docker compose up -d

# Установить зависимости
docker compose exec php composer install

# Применить миграции
docker compose exec php ./yii migrate

# Загрузить тестовые тендеры
docker compose exec php ./yii tender:ingest --source=fake --page-size=50

# Запустить worker уведомлений (в отдельном терминале)
docker compose exec php ./yii worker:notifications
```

Приложение доступно по адресу: <http://localhost:8080>

Swagger UI: <http://localhost:8080/docs/>

RabbitMQ Management: <http://localhost:15672> (guest/guest)

### Переменные окружения

Скопируйте `.env.example` в `.env` и заполните:

```env
DB_DSN=pgsql:host=postgres;port=5432;dbname=tender_watch
DB_USER=app
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379

RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest

MAILER_DSN=smtp://mailhog:1025
MAILER_FROM=noreply@tenderwatch.ru

TELEGRAM_BOT_TOKEN=your_token_here
```

## API

Все эндпоинты за префиксом `/api/v1`. Аутентификация: заголовок `X-Api-Key`.

| Метод    | Путь                  | Описание                                               |
| -------- | --------------------- | ------------------------------------------------------ |
| GET      | `/health`             | Состояние сервисов                                     |
| POST     | `/auth/login`         | Получить токен                                         |
| GET      | `/tenders`            | Список тендеров (q, region, min\_budget, max\_budget, page)        |
| GET      | `/tenders/{id}`       | Тендер по UUID                                         |
| GET      | `/categories`         | Дерево категорий                                       |
| POST     | `/subscriptions`      | Создать подписку                                       |
| GET      | `/subscriptions`      | Список подписок пользователя                           |
| PATCH    | `/subscriptions/{id}` | Обновить подписку                                      |
| DELETE   | `/subscriptions/{id}` | Удалить подписку                                       |
| GET      | `/subscriptions/preview` | Предпросмотр совпадений по критериям (count + sample) |
| GET      | `/notifications`      | Лог уведомлений пользователя (с пагинацией)           |

### Примеры

```bash
# Получить токен
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@example.com","password":"secret"}'

# Список тендеров
curl http://localhost:8080/api/v1/tenders?q=дорога&region=Москва \
  -H 'X-Api-Key: your-key'

# Создать подписку
curl -X POST http://localhost:8080/api/v1/subscriptions \
  -H 'Authorization: Bearer <token>' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Дороги Москвы",
    "criteria": {
      "keywords": ["дорога", "ремонт"],
      "regions": ["Москва"],
      "min_budget": 1000000
    },
    "channels": ["email"]
  }'
```

## CLI-команды

```bash
# Миграции
./yii migrate          # Применить
./yii migrate --down   # Откатить

# Ingestion
./yii tender:ingest --source=fake       # 100 синтетических тендеров
./yii tender:ingest --source=zakupki    # Парсинг zakupki.gov.ru
./yii tender:ingest --source=fake --page-size=200

# Worker
./yii worker:notifications   # Запустить обработчик уведомлений
```

## Разработка

```bash
# PHPUnit тесты
./vendor/bin/phpunit tests/Unit --testdox

# PHPStan статический анализ (уровень 6)
./vendor/bin/phpstan analyse src --level=6

# PHP_CodeSniffer — проверка кодстайла PSR-12
./vendor/bin/phpcs --standard=phpcs.xml

# Автоисправление (phpcbf)
./vendor/bin/phpcbf --standard=phpcs.xml

# Генерация OpenAPI-спецификации (docs/openapi.json + public/docs/openapi.json)
./vendor/bin/openapi src -o public/docs/openapi.json --format json

# Пересборка конфига Yii3 после изменений config/configuration.php
composer yii-config-rebuild

# Обновить автозагрузчик
composer dump-autoload
```

## ADR (Architecture Decision Records)

### ADR-001: yiisoft/app-api как базовый шаблон

**Решение**: Использовать `yiisoft/app-api` вместо `yiisoft/app`.

**Причина**: `yiisoft/app` включает view-слой, сессии и CSRF — избыточно для REST API. `app-api` содержит только PSR-7/PSR-15 pipeline без лишних зависимостей.

### ADR-002: DDD + Hexagonal Architecture

**Решение**: Разделение на Domain / Application / Infrastructure / Presentation слои в каждом bounded context.

**Причина**: Бизнес-логика (MatchingEngine, Tender aggregate) изолирована от фреймворка и БД. Это упрощает unit-тестирование без поднятия инфраструктуры.

### ADR-003: Specification Pattern для матчинга

**Решение**: `CategorySpec.and(BudgetSpec).and(RegionSpec).and(KeywordSpec)`.

**Причина**: Критерии фильтрации комбинируемы и расширяемы без изменения MatchingEngine. Каждая спецификация тестируется независимо.

### ADR-004: php-amqplib вместо yiisoft/queue

**Решение**: Прямое использование `php-amqplib/php-amqplib` для RabbitMQ.

**Причина**: На момент разработки `yiisoft/queue` нестабилен и отсутствует в шаблоне `app-api`. `php-amqplib` — зрелая библиотека с поддержкой AMQP 0-9-1, ack/nack и QoS.

### ADR-005: UUID v7 для идентификаторов

**Решение**: `Ramsey\Uuid\Uuid::uuid7()` для всех агрегатов.

**Причина**: UUID v7 монотонно возрастающий (содержит timestamp), что даёт хорошую локальность для индексов B-tree в PostgreSQL в отличие от UUID v4.

### ADR-006: Domain Events диспатчатся из репозитория

**Решение**: `TenderRepositoryInterface::save()` после персистирования вызывает `$eventDispatcher->dispatch()` для каждого события из `$tender->pullDomainEvents()`.

**Причина**: События должны диспатчиться только после успешного сохранения в БД (transactional consistency). Диспатч из конструктора агрегата нарушил бы этот инвариант.

## Используемые механики Yii3

- **DI Container** (`yiisoft/di`): фабрики в `config/common/di/*.php`
- **Config Plugin** (`yiisoft/config`): merge plan из `config/configuration.php`, группы `params`, `di`, `events`, `routes`
- **PSR-15 Middleware Pipeline**: `ErrorHandlingMiddleware` → `ApiKeyAuthMiddleware` → `RateLimitMiddleware` → Controller
- **PSR-14 Events** (`yiisoft/event-dispatcher`): `TenderCreated`, `TenderUpdated`, `SubscriptionMatched`
- **Router** (`yiisoft/router`): `Group::create('/api/v1')` с middleware группы
- **DB** (`yiisoft/db-pgsql`): `createCommand()` с параметрами, upsert через `ON CONFLICT`
- **Cache** (`yiisoft/cache-redis`): `CacheInterface` поверх Predis

## CI/CD

GitHub Actions (`.github/workflows/ci.yml`):

1. PHP 8.3 + extensions
2. Composer cache + install
3. PHPUnit `--testsuite Unit`
4. PHPStan level 6
5. PHP\_CodeSniffer PSR-12 (`phpcs.xml`)
6. Генерация `public/docs/openapi.json` (`zircote/swagger-php`)
