<?php

namespace App\Support\Notifications;

use App\Models\RtdOrder;
use App\Models\RtdProduct;
use App\Models\User;

/**
 * Builds role-aware notification copy for the RTD (Ready-to-Dispatch) marketplace,
 * e.g. "New RTD order from Acme Papers" / "500 × Kraft Box. Tap to accept or decline."
 *
 * Mirrors InquiryNotificationCopy so RTD notifications read consistently with the
 * rest of the app. Each method returns ['title' => ..., 'body' => ...].
 */
class RtdNotificationCopy
{
    /** Converter-facing: a brand placed a new order request. */
    public static function orderRequested(RtdOrder $order, string $brandName, string $productName): array
    {
        return [
            'title' => "New RTD order from {$brandName}",
            'body' => self::qty($order->quantity) . " {$productName}. Tap to accept or decline.",
        ];
    }

    /** Brand-facing: the converter accepted the order. */
    public static function orderAccepted(string $converterName, string $productName): array
    {
        return [
            'title' => "Order accepted by {$converterName}",
            'body' => "Your order for {$productName} was accepted. Complete payment to connect.",
        ];
    }

    /** Brand-facing: the converter declined the order. */
    public static function orderDeclined(string $converterName, string $productName): array
    {
        return [
            'title' => 'Order declined',
            'body' => "{$converterName} declined your order for {$productName}.",
        ];
    }

    /** Converter-facing: payment received, order is now connected. */
    public static function orderConnectedForConverter(string $brandName, string $productName): array
    {
        return [
            'title' => 'Payment received — order connected',
            'body' => "{$brandName} paid for {$productName}. You're now connected to coordinate the order.",
        ];
    }

    /** Brand-facing: payment confirmed, order is now connected. */
    public static function orderConnectedForBrand(string $converterName, string $productName): array
    {
        return [
            'title' => "You're connected",
            'body' => "Payment confirmed for {$productName}. You're now connected with {$converterName}.",
        ];
    }

    /** Converter-facing: the brand cancelled the accepted order. */
    public static function orderCancelled(string $brandName, string $productName): array
    {
        return [
            'title' => 'Order cancelled',
            'body' => "{$brandName} cancelled the order for {$productName}.",
        ];
    }

    /** Converter-facing: they missed the acceptance window. */
    public static function orderExpiredForConverter(string $productName): array
    {
        return [
            'title' => 'Order request expired',
            'body' => "You missed the acceptance window for a {$productName} order.",
        ];
    }

    /** Brand-facing: no converter accepted in time. */
    public static function orderExpiredForBrand(string $productName): array
    {
        return [
            'title' => 'Order request expired',
            'body' => "No converter accepted your {$productName} order in time.",
        ];
    }

    /** Converter-facing: product auto-paused after a declined/expired order. */
    public static function productPaused(string $productName): array
    {
        return [
            'title' => 'Product paused',
            'body' => "'{$productName}' was paused after a declined or expired order. Resume it to keep receiving orders.",
        ];
    }

    /** Converter-facing: product auto-deactivated after repeated declines/expiries. */
    public static function productDeactivated(string $productName): array
    {
        return [
            'title' => 'Product deactivated',
            'body' => "'{$productName}' was deactivated after repeated declined or expired orders. Review and relist it.",
        ];
    }

    /** Brand-facing broadcast: a converter listed a new RTD product. */
    public static function productAvailable(string $converterName, string $productName): array
    {
        return [
            'title' => 'New product available',
            'body' => "{$converterName} listed {$productName}. Tap to view and order.",
        ];
    }

    /** Best-effort display name for a party: company name, then personal name. */
    public static function displayName(?User $user): string
    {
        if (!$user) {
            return 'A user';
        }
        $company = trim((string) ($user->company_name ?? ''));
        if ($company !== '') {
            return $company;
        }
        $name = trim((string) ($user->name ?? ''));
        return $name !== '' ? $name : 'A user';
    }

    /** Readable product label from a product model. */
    public static function productName(?RtdProduct $product): string
    {
        if (!$product) {
            return 'a product';
        }
        $name = trim((string) ($product->product_name ?? ''));
        if ($name !== '') {
            return $name;
        }
        $category = trim((string) ($product->category ?? ''));
        return $category !== '' ? $category : 'a product';
    }

    private static function qty($quantity): string
    {
        $q = (float) $quantity;
        if ($q <= 0) {
            return '';
        }
        return number_format($q, 0) . ' ×';
    }
}
