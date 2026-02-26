<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Domain\MatchEngine\Models\MatchHistory;
use App\Models\MatchmakingLog;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('matchmaking:backfill-machine-dealer-logs {--dry-run : Preview only, do not write}', function () {
    $dryRun = (bool) $this->option('dry-run');

    $rows = MatchHistory::query()
        ->where('matched_role', 'machineDealer')
        ->orWhere('matched_role', 'machine-dealer')
        ->get();

    $created = 0;
    $skipped = 0;

    foreach ($rows as $row) {
        $inquiry = Inquiry::find($row->inquiry_id);
        $user = User::find($row->matched_user_id);
        $machineDealerId = $user?->machineDealer?->id;

        if (!$inquiry || !$user || !$machineDealerId) {
            $skipped++;
            continue;
        }

        $key = [
            'inquiry_id' => $inquiry->id,
            'machine_dealer_id' => $machineDealerId,
        ];

        $exists = MatchmakingLog::where($key)->exists();
        if ($exists) {
            $skipped++;
            continue;
        }

        if (!$dryRun) {
            DB::transaction(function () use ($inquiry, $row, $key) {
                MatchmakingLog::updateOrCreate($key, [
                    'session_id' => $inquiry->session?->id,
                    'is_visible' => true,
                    'visible_to_dealer_at' => now(),
                    'material_match' => (bool) data_get($row->reason_json, 'material_match', false),
                    'finish_match' => (bool) data_get($row->reason_json, 'finish_match', false),
                    'thickness_match' => (bool) data_get($row->reason_json, 'thickness_match', false),
                    'location_match' => (bool) data_get($row->reason_json, 'location_match', false),
                    'priority_score' => (int) ($row->match_score ?? 0),
                    'score_breakdown' => $row->reason_json ?? [],
                ]);
            });
        }

        $created++;
    }

    $this->info('Backfill completed.');
    $this->line('Mode: ' . ($dryRun ? 'dry-run' : 'write'));
    $this->line('Candidates scanned: ' . $rows->count());
    $this->line('Would create/Created: ' . $created);
    $this->line('Skipped: ' . $skipped);
})->purpose('Backfill missing matchmaking_logs for machineDealer match_history rows');
