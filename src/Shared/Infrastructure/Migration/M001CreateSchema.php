<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Migration;

use Yiisoft\Db\Connection\ConnectionInterface;

final class M001CreateSchema
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function up(): void
    {
        $this->db->createCommand(<<<SQL
            CREATE EXTENSION IF NOT EXISTS "pgcrypto";

            CREATE TABLE IF NOT EXISTS users (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                telegram_chat_id VARCHAR(100),
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            );

            CREATE TABLE IF NOT EXISTS api_tokens (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                token_hash VARCHAR(64) NOT NULL UNIQUE,
                expires_at TIMESTAMPTZ,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            );

            CREATE TABLE IF NOT EXISTS categories (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                parent_id UUID REFERENCES categories(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS sources (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                name VARCHAR(255) NOT NULL,
                type VARCHAR(50) NOT NULL,
                config_json JSONB NOT NULL DEFAULT '{}',
                last_sync_at TIMESTAMPTZ
            );

            CREATE TABLE IF NOT EXISTS tenders (
                id UUID PRIMARY KEY,
                title VARCHAR(500) NOT NULL,
                description TEXT NOT NULL DEFAULT '',
                category_id UUID REFERENCES categories(id),
                budget_amount BIGINT NOT NULL DEFAULT 0,
                budget_currency CHAR(3) NOT NULL DEFAULT 'RUB',
                region VARCHAR(255) NOT NULL DEFAULT '',
                published_at TIMESTAMPTZ NOT NULL,
                deadline_at TIMESTAMPTZ NOT NULL,
                source_id VARCHAR(255) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            );

            CREATE INDEX IF NOT EXISTS idx_tenders_status ON tenders(status);
            CREATE INDEX IF NOT EXISTS idx_tenders_category ON tenders(category_id);
            CREATE INDEX IF NOT EXISTS idx_tenders_region ON tenders(region);
            CREATE INDEX IF NOT EXISTS idx_tenders_budget ON tenders(budget_amount);
            CREATE INDEX IF NOT EXISTS idx_tenders_source ON tenders(source_id);
            CREATE UNIQUE INDEX IF NOT EXISTS idx_tenders_source_unique ON tenders(source_id);

            CREATE TABLE IF NOT EXISTS subscriptions (
                id UUID PRIMARY KEY,
                user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                name VARCHAR(255) NOT NULL,
                criteria_json JSONB NOT NULL DEFAULT '{}',
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            );

            CREATE INDEX IF NOT EXISTS idx_subscriptions_user ON subscriptions(user_id);
            CREATE INDEX IF NOT EXISTS idx_subscriptions_active ON subscriptions(is_active);

            CREATE TABLE IF NOT EXISTS subscription_channels (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                subscription_id UUID NOT NULL REFERENCES subscriptions(id) ON DELETE CASCADE,
                channel_type VARCHAR(20) NOT NULL,
                target VARCHAR(255) NOT NULL DEFAULT ''
            );

            CREATE TABLE IF NOT EXISTS matches (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tender_id UUID NOT NULL REFERENCES tenders(id) ON DELETE CASCADE,
                subscription_id UUID NOT NULL REFERENCES subscriptions(id) ON DELETE CASCADE,
                matched_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                UNIQUE(tender_id, subscription_id)
            );

            CREATE TABLE IF NOT EXISTS notifications_log (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                match_id UUID REFERENCES matches(id) ON DELETE SET NULL,
                channel_type VARCHAR(20) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                sent_at TIMESTAMPTZ,
                error TEXT,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            );
        SQL)->execute();
    }

    public function down(): void
    {
        $this->db->createCommand(<<<SQL
            DROP TABLE IF EXISTS notifications_log CASCADE;
            DROP TABLE IF EXISTS matches CASCADE;
            DROP TABLE IF EXISTS subscription_channels CASCADE;
            DROP TABLE IF EXISTS subscriptions CASCADE;
            DROP TABLE IF EXISTS tenders CASCADE;
            DROP TABLE IF EXISTS sources CASCADE;
            DROP TABLE IF EXISTS categories CASCADE;
            DROP TABLE IF EXISTS api_tokens CASCADE;
            DROP TABLE IF EXISTS users CASCADE;
        SQL)->execute();
    }
}
