<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

/**
 * Delivers push notifications to a user's devices via Firebase Cloud Messaging
 * (HTTP v1, through kreait/firebase-php).
 *
 * Every message carries both a `notification` block (so the OS renders it when
 * the app is backgrounded/killed) and a `data` block (so the app can route the
 * tap through the same resolver the in-app feed uses). The `data` keys mirror
 * the frontend contract: type, navigation_type, navigation_id, meta (JSON).
 *
 * Invalid / unregistered tokens returned by FCM are pruned automatically.
 */
class PushNotificationService
{
    public function __construct(
        protected DeviceTokenService $deviceTokenService
    ) {
    }

    /**
     * Send a push to every device registered for the given user.
     *
     * @param  array<string, mixed>  $data  Routing payload (stringified for FCM).
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        if (! config('services.push.enabled', true)) {
            return;
        }

        $tokens = DeviceToken::where('user_id', $userId)->pluck('token')->all();
        if ($tokens === []) {
            return;
        }

        $message = CloudMessage::new()
            ->withNotification(FcmNotification::create($title, $body))
            ->withData($this->stringifyData($data))
            ->withAndroidConfig($this->androidConfig())
            ->withApnsConfig($this->apnsConfig());

        /** @var Messaging $messaging */
        $messaging = app(Messaging::class);

        $report = $messaging->sendMulticast($message, $tokens);

        // Drop tokens FCM says are dead so we stop paying for them.
        $dead = array_merge($report->invalidTokens(), $report->unknownTokens());
        if ($dead !== []) {
            $this->deviceTokenService->prune($dead);
        }

        if ($report->hasFailures()) {
            Log::info('Push partially failed', [
                'user_id' => $userId,
                'sent' => $report->successes()->count(),
                'failed' => $report->failures()->count(),
                'pruned' => count($dead),
            ]);
        }
    }

    /**
     * FCM data values must all be strings; JSON-encode anything non-scalar.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    protected function stringifyData(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }
            $out[$key] = is_scalar($value)
                ? (string) $value
                : (string) json_encode($value);
        }

        return $out;
    }

    protected function androidConfig(): AndroidConfig
    {
        return AndroidConfig::fromArray([
            'priority' => 'high',
            'notification' => [
                'channel_id' => config('services.push.android_channel_id', 'default'),
                'sound' => 'default',
            ],
        ]);
    }

    protected function apnsConfig(): ApnsConfig
    {
        return ApnsConfig::fromArray([
            'headers' => [
                'apns-priority' => '10',
            ],
            'payload' => [
                'aps' => [
                    'sound' => 'default',
                ],
            ],
        ]);
    }
}
