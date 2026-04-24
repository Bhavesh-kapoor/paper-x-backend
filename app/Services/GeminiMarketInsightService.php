<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiMarketInsightService
{
    /**
     * @param array<int, array<string, mixed>> $articles
     * @return array{insight_text: string, sentiment: string}
     */
    public function generateInsight(array $articles): array
    {
        $apiKey = (string) config('services.gemini.api_key');
        $baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $model = (string) config('services.gemini.model', 'gemini-flash-latest');

        if ($apiKey === '') {
            throw new \RuntimeException('Gemini API key is missing. Set GEMINI_API_KEY in environment.');
        }

        $prompt = $this->buildPrompt($articles);
        $endpoint = sprintf('%s/models/%s:generateContent', $baseUrl, $model);

        $response = null;
        $attempts = 3;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $response = Http::timeout(40)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-goog-api-key' => $apiKey,
                ])
                ->post($endpoint, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                break;
            }

            // Gemini occasionally returns temporary 503 under demand spikes.
            if ($response->status() === 503 && $attempt < $attempts) {
                sleep($attempt * 2);
                continue;
            }

            break;
        }

        if (!$response || !$response->successful()) {
            Log::error('Gemini insight generation failed', [
                'status' => $response?->status(),
                'body' => $response?->json(),
            ]);
            throw new \RuntimeException('Gemini API request failed.');
        }

        $payload = $response->json();
        $insightText = $this->extractInsightText($payload);

        if ($insightText === '') {
            throw new \RuntimeException('Gemini API returned empty insight text.');
        }

        return [
            'insight_text' => $insightText,
            'sentiment' => $this->extractSentiment($insightText),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $articles
     */
    private function buildPrompt(array $articles): string
    {
        $headlineLines = array_map(
            static function (array $article, int $index): string {
                $title = (string) ($article['title'] ?? 'Untitled');
                $source = (string) ($article['source'] ?? 'Unknown Source');
                $publishedAt = (string) ($article['published_at'] ?? '');
                return sprintf('%d. %s | Source: %s | Published: %s', $index + 1, $title, $source, $publishedAt);
            },
            $articles,
            array_keys($articles)
        );

        return implode("\n", [
            'You are a senior market analyst for the global paper and pulp industry.',
            'Using only the provided headlines, generate a concise daily market insight report.',
            'Write clearly for paper mill operators and procurement leaders.',
            '',
            'Required output sections (in this exact order):',
            '1) Market Summary',
            '2) Key Trends',
            '3) Supply & Demand Update',
            '4) Regional Highlights (Asia, Europe, Americas)',
            '5) Market Sentiment (Bullish/Bearish/Neutral) with one-line reason',
            '6) Top 3 Actionable Insights for paper mill operators',
            '',
            'Headlines:',
            implode("\n", $headlineLines),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractInsightText(array $payload): string
    {
        $parts = data_get($payload, 'candidates.0.content.parts', []);
        if (!is_array($parts)) {
            return '';
        }

        $textParts = [];
        foreach ($parts as $part) {
            $text = isset($part['text']) ? trim((string) $part['text']) : '';
            if ($text !== '') {
                $textParts[] = $text;
            }
        }

        return trim(implode("\n\n", $textParts));
    }

    private function extractSentiment(string $insightText): string
    {
        $normalized = strtolower($insightText);

        if (str_contains($normalized, 'bullish')) {
            return 'bullish';
        }

        if (str_contains($normalized, 'bearish')) {
            return 'bearish';
        }

        if (str_contains($normalized, 'neutral')) {
            return 'neutral';
        }

        return 'neutral';
    }
}
