<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\MarketInsight;
use App\Services\MarketInsightGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class MarketInsightController extends Controller
{
    public function __construct(
        protected MarketInsightGeneratorService $marketInsightGeneratorService
    ) {
    }

    /**
     * Generate today's insight AFTER the response is flushed, so no user ever
     * waits on the RSS + Gemini pipeline. A short-lived cache flag makes sure
     * concurrent requests don't kick off duplicate generations.
     */
    private function queueTodayGeneration(string $today): void
    {
        $lockKey = 'market-insight:generating:'.$today;
        $lockAcquired = false;

        try {
            // Cache::add() is atomic — only the first caller acquires it.
            if (! Cache::add($lockKey, true, now()->addMinutes(10))) {
                return; // another request is already generating today's insight
            }
            $lockAcquired = true;
        } catch (\Throwable $e) {
            // Cache unavailable (missing cache table, driver misconfigured, etc.).
            // Degrade gracefully: still generate, just without de-duplication.
            // generateForDate() re-checks for an existing row, so a duplicate
            // run is wasteful at worst, never incorrect.
            Log::warning('Market insight lock unavailable; generating without it', [
                'error' => $e->getMessage(),
            ]);
        }

        dispatch(function () use ($today, $lockKey, $lockAcquired) {
            try {
                app(MarketInsightGeneratorService::class)
                    ->generateForDate(\Illuminate\Support\Carbon::parse($today));
            } catch (\Throwable $e) {
                Log::warning('Background market insight generation failed', [
                    'insight_date' => $today,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                if ($lockAcquired) {
                    try {
                        Cache::forget($lockKey);
                    } catch (\Throwable $e) {
                        // Cache went away mid-flight; the 10-min TTL clears it.
                    }
                }
            }
        })->afterResponse();
    }

    public function today()
    {
        try {
            $today = now()->toDateString();

            // Fast path: today's insight already exists (scheduler ran, or an
            // earlier background generation finished) — plain DB read.
            $existing = MarketInsight::query()->whereDate('insight_date', $today)->first();
            if ($existing) {
                return Response::success(
                    'Today market insight retrieved successfully',
                    $this->transformInsight($existing)
                );
            }

            // Today's isn't ready yet. NEVER make the user wait on the
            // RSS + Gemini pipeline (that took 30s+). Serve the most recent
            // insight instantly and generate today's after the response.
            $latest = MarketInsight::query()->orderByDesc('insight_date')->first();

            if ($latest) {
                // Queueing must never affect what the user sees — if anything
                // here fails (cache down, dispatcher error), we still serve the
                // last stored insight from the database.
                try {
                    $this->queueTodayGeneration($today);
                } catch (\Throwable $e) {
                    Log::warning('Could not queue market insight generation', [
                        'error' => $e->getMessage(),
                    ]);
                }

                return Response::success(
                    'Latest market insight retrieved successfully',
                    $this->transformInsight($latest)
                );
            }

            // Nothing at all in the table (very first run) — generate inline as
            // a last resort so the screen isn't empty.
            @set_time_limit(120);
            $result = $this->marketInsightGeneratorService->generateForDate(now());

            return Response::success(
                $result['generated']
                    ? 'Today market insight generated successfully'
                    : 'Today market insight retrieved successfully',
                $this->transformInsight($result['insight'])
            );
        } catch (\Throwable $exception) {
            // Generation failed (e.g. Gemini overloaded / RSS unreachable) —
            // serve the most recent insight instead of an empty error screen.
            $fallback = MarketInsight::query()->orderByDesc('insight_date')->first();

            if ($fallback) {
                Log::warning('Today insight generation failed; serving latest available', [
                    'error' => $exception->getMessage(),
                    'fallback_date' => (string) $fallback->insight_date,
                ]);

                return Response::success(
                    'Latest market insight retrieved successfully',
                    $this->transformInsight($fallback)
                );
            }

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
