<?php

namespace App\Support\Chat;

use App\Models\User;

class ChatMessageFormatter
{
    public static function buildInitialInterestMessage(
        ?float $approxPrice,
        ?string $description,
        ?string $companyName = null
    ): string {
        $normalizedDescription = trim((string) ($description ?? ''));
        $hasDescription = $normalizedDescription !== '';
        $hasPrice = $approxPrice !== null;

        $lines = [
            'Hello,',
            '',
            'Thank you for your requirement.',
            '',
            'Here are my details:',
            '',
        ];

        if ($hasPrice) {
            $lines[] = 'Approximate Price: ' . self::formatRupeeAmount((float) $approxPrice);
            $lines[] = '';
        }

        if ($hasDescription) {
            $lines[] = 'Details:';
            $lines[] = $normalizedDescription;
            $lines[] = '';
        } elseif (!$hasPrice) {
            $lines[] = 'I am interested in this requirement and would like to discuss details in chat.';
            $lines[] = '';
        }

        $lines[] = 'Looking forward to discussing further.';
        $lines[] = '';

        $normalizedCompanyName = trim((string) ($companyName ?? ''));
        if ($normalizedCompanyName !== '') {
            $lines[] = 'Best regards,';
            $lines[] = $normalizedCompanyName;
        } else {
            $lines[] = 'Best regards.';
        }

        return implode("\n", $lines);
    }

    public static function resolveResponderDisplayName(User $responder): string
    {
        $companyName = trim((string) ($responder->company_name ?? ''));
        if ($companyName !== '') {
            return $companyName;
        }

        $name = trim((string) ($responder->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        return 'Responder';
    }

    private static function formatRupeeAmount(float $amount): string
    {
        return '₹' . number_format($amount, 2, '.', ',');
    }
}
