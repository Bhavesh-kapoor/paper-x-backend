<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\MarketInsight;
use App\Services\MarketInsightGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class MarketInsightController extends Controller
{
    public function __construct(
        protected MarketInsightGeneratorService $marketInsightGeneratorService
    ) {
    }

    public function today()
    {
        try {
            // First-hit generation may include RSS + AI calls; allow longer request time.
            @set_time_limit(120);

            $result = $this->marketInsightGeneratorService->generateForDate(now());

            return Response::success(
                $result['generated']
                    ? 'Today market insight generated successfully'
                    : 'Today market insight retrieved successfully',
                $this->transformInsight($result['insight'])
            );
        } catch (\Throwable $exception) {
            return Response::error(
                'Failed to fetch today market insight: '.$exception->getMessage(),
                null,
                HttpResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function history(Request $request)
    {
        try {
            $days = (int) $request->query('days', 7);
            $days = min(max($days, 1), 30);

            $insights = MarketInsight::query()
                ->orderByDesc('insight_date')
                ->limit($days)
                ->get()
                ->map(fn (MarketInsight $insight) => $this->transformInsight($insight))
                ->values();

            return Response::success('Market insight history retrieved successfully', [
                'days' => $days,
                'items' => $insights,
            ]);
        } catch (\Throwable $exception) {
            return Response::error(
                'Failed to fetch market insight history: '.$exception->getMessage(),
                null,
                HttpResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function showByDate(string $date)
    {
        try {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return Response::error(
                    'Invalid date format. Expected YYYY-MM-DD.',
                    null,
                    HttpResponse::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $insight = MarketInsight::query()
                ->whereDate('insight_date', $date)
                ->first();

            if (!$insight) {
                return Response::error(
                    'Market insight not found for the provided date.',
                    null,
                    HttpResponse::HTTP_NOT_FOUND
                );
            }

            return Response::success(
                'Market insight retrieved successfully',
                $this->transformInsight($insight)
            );
        } catch (\Throwable $exception) {
            return Response::error(
                'Failed to fetch market insight by date: '.$exception->getMessage(),
                null,
                HttpResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function transformInsight(MarketInsight $insight): array
    {
        return [
            'id' => $insight->id,
            'insight_date' => $insight->insight_date?->toDateString(),
            'insight_text' => $insight->insight_text,
            'sentiment' => $insight->sentiment,
            'articles' => $insight->articles ?? [],
            'created_at' => $insight->created_at?->toIso8601String(),
            'updated_at' => $insight->updated_at?->toIso8601String(),
        ];
    }
}
