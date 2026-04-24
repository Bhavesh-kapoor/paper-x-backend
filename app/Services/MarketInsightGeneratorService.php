<?php

namespace App\Services;

use App\Models\MarketInsight;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketInsightGeneratorService
{
    public function __construct(
        protected MarketNewsFetcherService $marketNewsFetcherService,
        protected GeminiMarketInsightService $geminiMarketInsightService
    ) {
    }

    /**
     * @return array{insight: MarketInsight, generated: bool}
     */
    public function generateForDate(?CarbonInterface $date = null): array
    {
        $insightDate = ($date ?? now())->toDateString();

        $existing = MarketInsight::query()
            ->whereDate('insight_date', $insightDate)
            ->first();

        if ($existing) {
            return [
                'insight' => $existing,
                'generated' => false,
            ];
        }

        $articles = $this->marketNewsFetcherService->fetchLatestArticles(25);
        if (empty($articles)) {
            throw new \RuntimeException('No market news articles available to generate insights.');
        }

        $result = $this->geminiMarketInsightService->generateInsight($articles);

        $insight = DB::transaction(function () use ($insightDate, $result, $articles) {
            return MarketInsight::query()->create([
                'insight_date' => $insightDate,
                'insight_text' => $result['insight_text'],
                'sentiment' => $result['sentiment'],
                'articles' => $articles,
            ]);
        });

        Log::info('Market insight generated', [
            'insight_date' => $insightDate,
            'sentiment' => $insight->sentiment,
            'article_count' => count($articles),
        ]);

        return [
            'insight' => $insight,
            'generated' => true,
        ];
    }
}
