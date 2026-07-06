<?php

namespace App\Services;

use App\Models\RtdOrderPaymentOrder;
use App\Models\User;
use App\Models\WalletPaymentOrder;
use Illuminate\Support\Collection;

class InvoiceService
{
    /**
     * Paginated, normalized list of the user's paid payment orders across
     * wallet purchases (credit packs + direct pay) and RTD platform fees.
     *
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function listForUser(User $user, int $page = 1, int $perPage = 15): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min($perPage, 50));

        $wallet = WalletPaymentOrder::query()
            ->where('user_id', $user->id)
            ->where('status', WalletPaymentOrder::STATUS_PAID)
            ->with('creditPack:id,name')
            ->orderByDesc('paid_at')
            ->get()
            ->map(fn (WalletPaymentOrder $order) => $this->normalizeWalletOrder($order));

        $rtd = RtdOrderPaymentOrder::query()
            ->where('user_id', $user->id)
            ->where('status', RtdOrderPaymentOrder::STATUS_PAID)
            ->with('rtdOrder:id,subtotal,commission_percent,commission_amount,gst_percent,gst_amount,total_amount')
            ->orderByDesc('paid_at')
            ->get()
            ->map(fn (RtdOrderPaymentOrder $order) => $this->normalizeRtdOrder($order));

        /** @var Collection $merged */
        $merged = $wallet->concat($rtd)
            ->sortByDesc(fn (array $row) => $row['paid_at'] ?? '')
            ->values();

        $total = $merged->count();
        $items = $merged->slice(($page - 1) * $perPage, $perPage)->values()->all();

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int) max(1, ceil($total / $perPage)),
            ],
        ];
    }

    /**
     * Resolve one invoice by its key ("W-{id}" or "R-{id}"), scoped to the user.
     */
    public function findForUser(User $user, string $key): array
    {
        [$type, $id] = $this->parseKey($key);

        if ($type === 'W') {
            $order = WalletPaymentOrder::query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->where('status', WalletPaymentOrder::STATUS_PAID)
                ->with('creditPack:id,name')
                ->firstOrFail();

            $invoice = $this->normalizeWalletOrder($order);
        } else {
            $order = RtdOrderPaymentOrder::query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->where('status', RtdOrderPaymentOrder::STATUS_PAID)
                ->with('rtdOrder:id,subtotal,commission_percent,commission_amount,gst_percent,gst_amount,total_amount')
                ->firstOrFail();

            $invoice = $this->normalizeRtdOrder($order);
        }

        $invoice['bill_to'] = $this->billTo($user);
        $invoice['seller']  = [
            'name'       => config('company.name'),
            'legal_name' => config('company.legal_name'),
            'address'    => config('company.address'),
            'gstin'      => config('company.gstin'),
            'email'      => config('company.email'),
            'phone'      => config('company.phone'),
        ];

        return $invoice;
    }

    public function billTo(User $user): array
    {
        return [
            'name'         => $user->name,
            'company_name' => $user->company_name,
            'gstin'        => $user->gst_in,
            'city'         => $user->city,
            'state'        => $user->state,
        ];
    }

    /**
     * @return array{0: string, 1: int} [type, id]
     */
    private function parseKey(string $key): array
    {
        if (!preg_match('/^(W|R)-(\d+)$/', strtoupper($key), $m)) {
            abort(404, 'Invoice not found');
        }

        return [$m[1], (int) $m[2]];
    }

    private function normalizeWalletOrder(WalletPaymentOrder $order): array
    {
        $meta      = $order->metadata ?? [];
        $isPack    = $order->credit_pack_id !== null || (!isset($meta['order_kind']) && !str_starts_with((string) $order->receipt, 'WEC-'));
        $totalInr  = round(((int) $order->amount_paise) / 100, 2);
        $gstInr    = round((float) ($meta['gst_amount_inr'] ?? 0), 2);
        $baseInr   = round($totalInr - $gstInr, 2);

        $packName = $order->creditPack?->name ?? ($meta['pack_name'] ?? null);
        $title = $isPack
            ? 'Credit Pack' . ($packName ? " — {$packName}" : '')
            : 'Credits Purchase (Direct Pay)';

        return [
            'key'                 => 'W-' . $order->id,
            'invoice_no'          => sprintf('ZUP-INV-W%06d', $order->id),
            'kind'                => $isPack ? 'credit_pack' : 'direct_pay',
            'title'               => $title,
            'base_amount_inr'     => $baseInr,
            'gst_percent'         => $gstInr > 0 ? (float) ($meta['gst_percentage'] ?? 18) : 0.0,
            'gst_amount_inr'      => $gstInr,
            'total_inr'           => $totalInr,
            'credits'             => (int) $order->credits,
            'currency'            => $order->currency,
            'paid_at'             => $order->paid_at?->toIso8601String(),
            'razorpay_payment_id' => $order->razorpay_payment_id,
            'razorpay_order_id'   => $order->razorpay_order_id,
            'receipt'             => $order->receipt,
            'status'              => 'PAID',
        ];
    }

    private function normalizeRtdOrder(RtdOrderPaymentOrder $order): array
    {
        $rtd      = $order->rtdOrder;
        $totalInr = round(((int) $order->amount_paise) / 100, 2);
        $gstInr   = round((float) ($rtd?->gst_amount ?? 0), 2);
        $baseInr  = round((float) ($rtd?->commission_amount ?? ($totalInr - $gstInr)), 2);

        return [
            'key'                 => 'R-' . $order->id,
            'invoice_no'          => sprintf('ZUP-INV-R%06d', $order->id),
            'kind'                => 'rtd_platform_fee',
            'title'               => 'RTD Platform Fee' . ($rtd ? " — Order #{$rtd->id}" : ''),
            'base_amount_inr'     => $baseInr,
            'gst_percent'         => $gstInr > 0 ? (float) ($rtd?->gst_percent ?? 18) : 0.0,
            'gst_amount_inr'      => $gstInr,
            'total_inr'           => $totalInr,
            'credits'             => null,
            'currency'            => $order->currency,
            'paid_at'             => $order->paid_at?->toIso8601String(),
            'razorpay_payment_id' => $order->razorpay_payment_id,
            'razorpay_order_id'   => $order->razorpay_order_id,
            'receipt'             => $order->receipt,
            'status'              => 'PAID',
        ];
    }
}
