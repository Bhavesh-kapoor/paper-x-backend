<?php

namespace App\Support;

/**
 * Converts an amount to words using the Indian numbering system
 * (Crore / Lakh / Thousand), formatted for a GST tax invoice.
 */
class AmountInWords
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    /**
     * e.g. inr(16332)      => "INR Sixteen Thousand Three Hundred Thirty Two Only"
     *      inr(2491.20)    => "INR Two Thousand Four Hundred Ninety One and Twenty paise Only"
     */
    public static function inr(float $amount): string
    {
        $amount  = round($amount, 2);
        $rupees  = (int) floor($amount);
        $paise   = (int) round(($amount - $rupees) * 100);

        $words = 'INR ' . self::words($rupees);

        if ($paise > 0) {
            $words .= ' and ' . self::words($paise) . ' paise';
        }

        return $words . ' Only';
    }

    private static function words(int $n): string
    {
        if ($n === 0) {
            return 'Zero';
        }

        $parts = [];

        $crore = intdiv($n, 10000000);
        $n %= 10000000;
        $lakh = intdiv($n, 100000);
        $n %= 100000;
        $thousand = intdiv($n, 1000);
        $n %= 1000;
        $hundred = intdiv($n, 100);
        $rest = $n % 100;

        if ($crore) {
            $parts[] = self::twoDigit($crore) . ' Crore';
        }
        if ($lakh) {
            $parts[] = self::twoDigit($lakh) . ' Lakh';
        }
        if ($thousand) {
            $parts[] = self::twoDigit($thousand) . ' Thousand';
        }
        if ($hundred) {
            $parts[] = self::ONES[$hundred] . ' Hundred';
        }
        if ($rest) {
            $parts[] = self::twoDigit($rest);
        }

        return trim(implode(' ', $parts));
    }

    private static function twoDigit(int $n): string
    {
        if ($n < 20) {
            return self::ONES[$n];
        }

        $ten = self::TENS[intdiv($n, 10)];
        $one = $n % 10;

        return $one ? "{$ten} " . self::ONES[$one] : $ten;
    }
}
