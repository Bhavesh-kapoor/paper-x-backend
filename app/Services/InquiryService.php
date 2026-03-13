<?php

namespace App\Services;

use App\Domain\MatchEngine\ResponseService as MatchEngineResponseService;
use App\Models\Inquiry;
use App\Models\MatchmakingLog;
use App\Models\User;
use App\Support\Chat\ChatMessageFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InquiryService
{
    public function __construct(
        protected MatchEngineResponseService $matchEngineResponseService,
        protected MatchmakingService $matchmakingService,
        protected ChatService $chatService
    ) {
    }

    /**
     * @return array{matchmaking_log_id: int, chat_message_created: bool, thread_id: int|null}
     */
    public function expressInterestWithAutoMessage(
        Inquiry $inquiry,
        User $user,
        mixed $approxPrice,
        ?string $description
    ): array {
        $log = $this->getMyMatchmakingLog($inquiry, $user);
        if (!$log) {
            throw new \Exception('You are not matched to this requirement', 403);
        }

        if ($log->declined_at) {
            throw new \Exception('You previously declined this requirement. Cannot express interest now.', 400);
        }

        $normalizedApproxPrice = ($approxPrice !== null && $approxPrice !== '') ? (float) $approxPrice : null;
        $normalizedDescription = trim((string) ($description ?? ''));

        // Keep existing V2 behavior.
        if (config('matchmaking.engine_version') === 'v2') {
            try {
                $this->matchEngineResponseService->respond(
                    $inquiry,
                    $user,
                    $normalizedDescription,
                    $normalizedApproxPrice,
                );
            } catch (\InvalidArgumentException $e) {
                throw new \Exception($e->getMessage(), 400, $e);
            }
        }

        $threadId = null;
        $chatMessageCreated = false;

        DB::transaction(function () use (
            $inquiry,
            $user,
            $log,
            $normalizedApproxPrice,
            $normalizedDescription,
            &$threadId,
            &$chatMessageCreated
        ) {
            $this->persistResponderInterest(
                $inquiry,
                $log,
                $normalizedApproxPrice,
                $normalizedDescription
            );

            $body = ChatMessageFormatter::buildInitialInterestMessage(
                $normalizedApproxPrice,
                $normalizedDescription,
                $user->company_name ?? null
            );

            try {
                $thread = $this->chatService->openOrCreateThread((int) $inquiry->id, $user);
                $threadId = (int) $thread->id;

                $chatMessageCreated = $this->chatService->sendAutoInitialMessageIfAbsent(
                    $thread,
                    $user,
                    $body
                );
            } catch (\Throwable $e) {
                Log::warning('express_interest_auto_message_failed', [
                    'inquiry_id' => (int) $inquiry->id,
                    'thread_id' => $threadId,
                    'responder_id' => (int) $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        return [
            'matchmaking_log_id' => (int) $log->id,
            'chat_message_created' => $chatMessageCreated,
            'thread_id' => $threadId,
        ];
    }

    private function persistResponderInterest(
        Inquiry $inquiry,
        MatchmakingLog $log,
        ?float $approxPrice,
        string $description
    ): void {
        $update = [
            'responded_at' => now(),
            'declined_at' => null,
        ];

        if ($approxPrice !== null) {
            $update['approx_price'] = $approxPrice;
        }

        if ($description !== '') {
            $update['interest_description'] = $description;
        }

        $log->update($update);

        // Auto-lock session when response threshold is reached.
        $this->matchmakingService->lockSessionIfResponseThresholdReached($inquiry, 10);
    }

    private function getMyMatchmakingLog(Inquiry $inquiry, User $user): ?MatchmakingLog
    {
        $query = $inquiry->matchmakingLogs();

        if ($user->dealer) {
            $query->where('dealer_id', $user->dealer->id);
        } elseif ($user->converter) {
            $query->where('converter_id', $user->converter->id);
        } elseif ($user->machineDealer) {
            $query->where('machine_dealer_id', $user->machineDealer->id);
        } else {
            return null;
        }

        return $query->first();
    }
}
