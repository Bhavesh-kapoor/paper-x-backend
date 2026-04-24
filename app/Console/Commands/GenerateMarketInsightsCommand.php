<?php

namespace App\Console\Commands;

use App\Services\MarketInsightGeneratorService;
use Illuminate\Console\Command;

class GenerateMarketInsightsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insights:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily market insights from RSS feeds using Gemini';

    public function __construct(
        protected MarketInsightGeneratorService $marketInsightGeneratorService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting daily market insights generation...');

        try {
            $result = $this->marketInsightGeneratorService->generateForDate(now());
            $insight = $result['insight'];

            if ($result['generated']) {
                $this->info(sprintf(
                    'Market insight generated for %s (sentiment: %s, articles: %d).',
                    $insight->insight_date->toDateString(),
                    $insight->sentiment,
                    is_array($insight->articles) ? count($insight->articles) : 0
                ));
            } else {
                $this->info(sprintf(
                    'Market insight already exists for %s. Skipping generation.',
                    $insight->insight_date->toDateString()
                ));
            }

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Market insights generation failed: '.$exception->getMessage());
            report($exception);

            return self::FAILURE;
        }
    }
}
