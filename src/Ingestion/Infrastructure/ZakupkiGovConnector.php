<?php

declare(strict_types=1);

namespace App\Ingestion\Infrastructure;

use App\Ingestion\Domain\RawTenderDTO;
use App\Ingestion\Domain\SourceConnectorInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Коннектор к открытым данным zakupki.gov.ru через API ЕИС (Единая информационная система).
 * Документация: https://zakupki.gov.ru/epz/main/public/download/downloadDocument.html?id=16849
 * Используем открытый RSS/REST без ключа (публичные данные).
 */
final class ZakupkiGovConnector implements SourceConnectorInterface
{
    private const BASE_URL = 'https://zakupki.gov.ru/epz/order/extendedsearch/results.html';

    private Client $client;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $searchQuery = '',
    ) {
        $this->client = new Client([
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'TenderWatch/1.0',
            ],
        ]);
    }

    public function getType(): string
    {
        return 'zakupki_gov';
    }

    public function fetch(int $page, int $pageSize): array
    {
        try {
            return $this->fetchFromOpenData($page, $pageSize);
        } catch (\Throwable $e) {
            $this->logger->warning('ZakupkiGov fetch failed, falling back to empty result', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function getTotalPages(int $pageSize): int
    {
        return 5;
    }

    /** @return RawTenderDTO[] */
    private function fetchFromOpenData(int $page, int $pageSize): array
    {
        $params = [
            'searchString' => $this->searchQuery,
            'morphology' => 'on',
            'pageNumber' => $page,
            'sortDirection' => 'false',
            'recordsPerPage' => '_' . $pageSize,
            'showLotsInfoHidden' => 'false',
            'sortBy' => 'UPDATE_DATE',
            'fz44' => 'on',
            'fz223' => 'on',
            'af' => 'on',
            'selectedLaws' => 'all',
        ];

        $response = $this->client->get(self::BASE_URL, ['query' => $params]);
        $body = (string)$response->getBody();

        return $this->parseHtmlResponse($body);
    }

    /** @return RawTenderDTO[] */
    private function parseHtmlResponse(string $html): array
    {
        $items = [];

        $dom = new \DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR);
        $xpath = new \DOMXPath($dom);

        $rows = $xpath->query('//div[contains(@class,"registry-entry__body")]');

        foreach ($rows as $row) {
            try {
                $item = $this->extractFromRow($xpath, $row);
                if ($item !== null) {
                    $items[] = $item;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $items;
    }

    private function extractFromRow(\DOMXPath $xpath, \DOMNode $row): ?RawTenderDTO
    {
        $titleNode = $xpath->query('.//div[contains(@class,"registry-entry__body-href")]//a', $row)->item(0);
        $priceNode = $xpath->query('.//div[contains(@class,"price-block__value")]', $row)->item(0);
        $dateNode = $xpath->query('.//div[contains(@class,"data-block__value")]', $row)->item(0);

        if (!$titleNode) {
            return null;
        }

        $title = trim($titleNode->textContent);
        $href = $titleNode instanceof \DOMElement ? $titleNode->getAttribute('href') : '';
        $externalId = 'zkp-' . md5($href ?: $title);

        $rawPrice = $priceNode ? preg_replace('/[^0-9]/', '', $priceNode->textContent) : '0';
        $amount = (int)($rawPrice ?: 0);

        $now = new \DateTimeImmutable();
        $deadline = new \DateTimeImmutable('+30 days');

        if ($dateNode) {
            try {
                $dateText = trim($dateNode->textContent);
                $parsed = \DateTimeImmutable::createFromFormat('d.m.Y', $dateText);
                if ($parsed !== false) {
                    $deadline = $parsed;
                }
            } catch (\Throwable) {
            }
        }

        return new RawTenderDTO(
            externalId: $externalId,
            title: mb_substr($title, 0, 500) ?: 'Без названия',
            description: $title,
            categoryName: 'Государственные закупки',
            budgetAmount: $amount,
            budgetCurrency: 'RUB',
            region: 'Россия',
            publishedAt: $now,
            deadlineAt: $deadline > $now ? $deadline : new \DateTimeImmutable('+30 days'),
            sourceType: $this->getType(),
            raw: ['href' => $href],
        );
    }
}
