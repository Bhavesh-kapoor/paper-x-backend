<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MarketNewsFetcherService
{
    /**
     * @var array<int, string>
     */
    private array $feedUrls = [
        'https://news.google.com/rss/search?q=paper+industry+market&hl=en&gl=US&ceid=US:en',
        'https://news.google.com/rss/search?q=paper+mill+pulp+prices&hl=en&gl=US&ceid=US:en',
        'https://news.google.com/rss/search?q=packaging+paper+demand&hl=en&gl=US&ceid=US:en',
        'https://news.google.com/rss/search?q=RISI+pulp+paper+market&hl=en&gl=US&ceid=US:en',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchLatestArticles(int $limit = 25): array
    {
        $articles = [];

        foreach ($this->feedUrls as $feedUrl) {
            try {
                $response = Http::timeout(15)
                    ->accept('application/rss+xml, application/xml, text/xml')
                    ->get($feedUrl);

                if (!$response->successful()) {
                    Log::warning('Market RSS feed request failed', [
                        'feed_url' => $feedUrl,
                        'status' => $response->status(),
                    ]);
                    continue;
                }

                $articles = array_merge($articles, $this->parseFeed($response->body(), $feedUrl));
            } catch (\Throwable $exception) {
                Log::warning('Market RSS feed processing failed', [
                    'feed_url' => $feedUrl,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $deduped = $this->dedupeByTitle($articles);

        usort($deduped, function (array $left, array $right): int {
            $leftDate = isset($left['published_at']) ? strtotime((string) $left['published_at']) : 0;
            $rightDate = isset($right['published_at']) ? strtotime((string) $right['published_at']) : 0;

            return $rightDate <=> $leftDate;
        });

        $latest = array_slice($deduped, 0, $limit);

        return array_map(function (array $article): array {
            $article['image_url'] = $this->extractOgImage((string) ($article['url'] ?? ''));

            return $article;
        }, $latest);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseFeed(string $xmlBody, string $feedUrl): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlBody);

        if ($xml === false || !isset($xml->channel->item)) {
            Log::warning('Market RSS XML parsing failed', [
                'feed_url' => $feedUrl,
                'errors' => array_map(static fn ($error) => trim($error->message), libxml_get_errors()),
            ]);
            libxml_clear_errors();

            return [];
        }

        $feedHost = parse_url($feedUrl, PHP_URL_HOST) ?: 'unknown';
        $rows = [];

        foreach ($xml->channel->item as $item) {
            $title = trim((string) ($item->title ?? ''));
            $url = trim((string) ($item->link ?? ''));
            $pubDateRaw = trim((string) ($item->pubDate ?? ''));
            $summaryRaw = trim((string) ($item->description ?? ''));
            $sourceNode = $item->source ?? null;
            $source = trim((string) $sourceNode);

            if ($title === '' || $url === '') {
                continue;
            }

            $rows[] = [
                'title' => $title,
                'url' => $url,
                'published_at' => $this->normalizeDate($pubDateRaw),
                'source' => $source !== '' ? $source : $feedHost,
                'summary' => Str::of(strip_tags($summaryRaw))->squish()->limit(500)->toString(),
                'category' => $this->detectCategory($title),
            ];
        }

        libxml_clear_errors();

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $articles
     * @return array<int, array<string, mixed>>
     */
    private function dedupeByTitle(array $articles): array
    {
        $seen = [];
        $deduped = [];

        foreach ($articles as $article) {
            $normalizedTitle = Str::of((string) ($article['title'] ?? ''))
                ->lower()
                ->squish()
                ->toString();

            if ($normalizedTitle === '' || isset($seen[$normalizedTitle])) {
                continue;
            }

            $seen[$normalizedTitle] = true;
            $deduped[] = $article;
        }

        return $deduped;
    }

    private function normalizeDate(string $pubDateRaw): string
    {
        if ($pubDateRaw === '') {
            return now()->toIso8601String();
        }

        try {
            return Carbon::parse($pubDateRaw)->toIso8601String();
        } catch (\Throwable) {
            return now()->toIso8601String();
        }
    }

    private function extractOgImage(string $articleUrl): ?string
    {
        if ($articleUrl === '') {
            return null;
        }

        try {
            $response = Http::timeout(3)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; PaperXInsightsBot/1.0)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->withOptions([
                    'stream' => true,
                    'allow_redirects' => true,
                ])
                ->get($articleUrl);

            if (!$response->successful()) {
                return null;
            }

            $contentType = strtolower((string) $response->header('Content-Type', ''));
            if ($contentType !== '' && !str_contains($contentType, 'text/html')) {
                return null;
            }

            $stream = $response->toPsrResponse()->getBody();
            $maxBytes = 262144;
            $html = '';

            while (!$stream->eof() && strlen($html) < $maxBytes) {
                $html .= $stream->read(8192);
            }

            if ($html === '') {
                return null;
            }

            $imagePatterns = [
                '/<meta\s+[^>]*(?:property|name)=["\']og:image["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i',
                '/<meta\s+[^>]*content=["\']([^"\']+)["\'][^>]*(?:property|name)=["\']og:image["\'][^>]*>/i',
                '/<meta\s+[^>]*(?:property|name)=["\']twitter:image["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i',
                '/<meta\s+[^>]*content=["\']([^"\']+)["\'][^>]*(?:property|name)=["\']twitter:image["\'][^>]*>/i',
            ];

            foreach ($imagePatterns as $pattern) {
                if (preg_match($pattern, $html, $matches) === 1) {
                    $imageUrl = trim((string) ($matches[1] ?? ''));
                    if ($imageUrl !== '' && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        return $imageUrl;
                    }
                }
            }

            $googleImageMatches = [];
            if (preg_match_all('/https:\/\/lh3\.googleusercontent\.com\/[^"\']+/i', $html, $googleImageMatches) > 0) {
                $candidates = array_values(array_unique($googleImageMatches[0] ?? []));
                usort($candidates, function (string $left, string $right): int {
                    $leftScore = str_contains($left, '=s0-w') ? 2 : (str_contains($left, '=w') ? 1 : 0);
                    $rightScore = str_contains($right, '=s0-w') ? 2 : (str_contains($right, '=w') ? 1 : 0);

                    if ($leftScore === $rightScore) {
                        return strlen($right) <=> strlen($left);
                    }

                    return $rightScore <=> $leftScore;
                });

                foreach ($candidates as $candidate) {
                    $imageUrl = $this->normalizeGoogleImageUrl(trim($candidate));
                    if ($imageUrl !== '' && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        return $imageUrl;
                    }
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('Failed to extract og:image for article', [
                'article_url' => $articleUrl,
                'error' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    private function normalizeGoogleImageUrl(string $imageUrl): string
    {
        if (!str_contains($imageUrl, 'googleusercontent.com')) {
            return $imageUrl;
        }

        return preg_replace('/=w\d+(?:-h\d+)?$/i', '=s0-w1200', $imageUrl) ?? $imageUrl;
    }

    private function detectCategory(string $title): string
    {
        $categoryMatchers = [
            'Market Prices' => '/price|cost|rate|market|index/i',
            'Supply Chain' => '/supply|shortage|chain|logistics|ship/i',
            'Packaging' => '/packaging|box|carton|corrugated/i',
            'Sustainability' => '/sustainab|recycle|green|carbon|environ/i',
            'Mills & Manufacturing' => '/mill|manufactur|plant|production|capacity/i',
            'Trade & Export' => '/export|import|trade|tariff|duty/i',
        ];

        foreach ($categoryMatchers as $category => $pattern) {
            if (preg_match($pattern, $title) === 1) {
                return $category;
            }
        }

        return 'Market Prices';
    }
}
