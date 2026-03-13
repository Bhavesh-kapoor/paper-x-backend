<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\PreRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Http;

class PreRegistrationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'primary_role' => ['required', 'string', 'in:dealer,converter,brand,machineDealer'],
            'notes' => ['nullable', 'string'],
            'source' => ['nullable', 'string', 'max:100'],
            'captcha_token' => ['required', 'string'],
        ]);

        // Captcha verification: in production expect a valid token; in local/dev we can skip when no secret is configured.
        if (!$this->verifyCaptcha($validated['captcha_token'])) {
            return Response::error('Invalid captcha', null, 422);
        }

        $normalizedMobile = preg_replace('/\D+/', '', $validated['mobile'] ?? '');
        if (strlen($normalizedMobile) === 10) {
            $validated['mobile'] = $normalizedMobile;
        }

        $ipAddress = $request->ip();
        $userAgent = (string) $request->header('User-Agent', '');

        $lead = PreRegistration::updateOrCreate(
            ['mobile' => $validated['mobile']],
            [
                'full_name' => $validated['full_name'],
                'email' => $validated['email'] ?? null,
                'company_name' => $validated['company_name'],
                'primary_role' => $validated['primary_role'],
                'notes' => $validated['notes'] ?? null,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'utm_source' => $request->input('utm_source'),
                'utm_medium' => $request->input('utm_medium'),
                'utm_campaign' => $request->input('utm_campaign'),
            ]
        );

        return Response::success('pre_registration.saved', [
            'id' => $lead->id,
        ]);
    }

    protected function verifyCaptcha(string $token): bool
    {
        $secret = config('services.recaptcha.secret');

        // If no secret is configured (e.g. local/dev), treat captcha as passed to avoid blocking.
        if (empty($secret) || app()->environment('local')) {
            return !empty($token);
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $token,
            ]);

            if (!$response->ok()) {
                return false;
            }

            $data = $response->json();

            return isset($data['success']) && $data['success'] === true;
        } catch (\Throwable $e) {
            // Fail closed in production if verification throws
            return false;
        }
    }
}

