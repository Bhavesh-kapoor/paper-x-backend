<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Msg91Service
{
    protected string $baseUrl = 'https://control.msg91.com/api/v5/flow';

    public function sendOtp(string $mobile, string $otp): bool
    {
        $authKey = config('services.msg91.auth_key');
        $templateId = config('services.msg91.template_id');

        if (!$authKey || !$templateId) {
            Log::error('MSG91 not configured: missing auth_key/template_id');
            return false;
        }

        Log::info('MSG91 OTP send requested', [
            'mobile' => $mobile,
            'template_id' => $templateId,
        ]);

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'authkey' => $authKey,
        ])->post($this->baseUrl, [
            'template_id' => $templateId,
            'short_url' => '0',
            'recipients' => [
                [
                    'mobiles' => '91' . $mobile,
                    'var1' => $otp,
                ],
            ],
        ]);

        if (!$response->successful() || ($response->json('type') !== 'success')) {
            Log::error('MSG91 OTP send failed', [
                'mobile' => $mobile,
                'template_id' => $templateId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        Log::info('MSG91 OTP send accepted', [
            'mobile' => $mobile,
            'template_id' => $templateId,
            'request_id' => $response->json('message'),
        ]);

        return true;
    }
}
