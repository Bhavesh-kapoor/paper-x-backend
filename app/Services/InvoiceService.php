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

    /**
     * Admin-facing: paginated, normalized list of ALL users' paid invoices,
     * with optional filters and an aggregate revenue summary.
     *
     * @param array{search?: string, kind?: string, date_from?: string, date_to?: string} $filters
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int>, summary: array<string, mixed>}
     */
    public function listAllForAdmin(array $filters, int $page = 1, int $perPage = 20): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min($perPage, 100));

        $kind = $filters['kind'] ?? null;

        $wallet = collect();
        if ($kind === null || $kind === 'credit_pack' || $kind === 'direct_pay') {
            $walletQuery = WalletPaymentOrder::query()
                ->where('status', WalletPaymentOrder::STATUS_PAID)
                ->with(['creditPack:id,name', 'user:id,name,company_name,mobile'])
                ->orderByDesc('paid_at');

            $this->applyAdminFilters($walletQuery, $filters);

            $wallet = $walletQuery->get()->map(function (WalletPaymentOrder $order) {
                $row = $this->normalizeWalletOrder($order);
                $row['user'] = $this->userSummary($order->user);

                return $row;
            });

            // A wallet order resolves to either credit_pack or direct_pay — narrow if asked.
            if ($kind === 'credit_pack' || $kind === 'direct_pay') {
                $wallet = $wallet->where('kind', $kind)->values();
            }
        }

        $rtd = collect();
        if ($kind === null || $kind === 'rtd_platform_fee') {
            $rtdQuery = RtdOrderPaymentOrder::query()
                ->where('status', RtdOrderPaymentOrder::STATUS_PAID)
                ->with(['rtdOrder:id,subtotal,commission_percent,commission_amount,gst_percent,gst_amount,total_amount', 'user:id,name,company_name,mobile'])
                ->orderByDesc('paid_at');

            $this->applyAdminFilters($rtdQuery, $filters);

            $rtd = $rtdQuery->get()->map(function (RtdOrderPaymentOrder $order) {
                $row = $this->normalizeRtdOrder($order);
                $row['user'] = $this->userSummary($order->user);

                return $row;
            });
        }

        /** @var Collection $merged */
        $merged = $wallet->concat($rtd)
            ->sortByDesc(fn (array $row) => $row['paid_at'] ?? '')
            ->values();

        $summary = $this->buildSummary($merged);

        $total = $merged->count();
        $items = $merged->slice(($page - 1) * $perPage, $perPage)->values()->all();

        return [
            'data'    => $items,
            'meta'    => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int) max(1, ceil($total / $perPage)),
            ],
            'summary' => $summary,
        ];
    }

    /**
     * Admin-facing: resolve one invoice by key ("W-{id}"/"R-{id}") without user scoping.
     * Includes bill_to/seller and the owner user summary (for building a download URL).
     */
    public function findAny(string $key): array
    {
        [$type, $id] = $this->parseKey($key);

        if ($type === 'W') {
            $order = WalletPaymentOrder::query()
                ->where('id', $id)
                ->where('status', WalletPaymentOrder::STATUS_PAID)
                ->with(['creditPack:id,name', 'user'])
                ->firstOrFail();

            $invoice = $this->normalizeWalletOrder($order);
        } else {
            $order = RtdOrderPaymentOrder::query()
                ->where('id', $id)
                ->where('status', RtdOrderPaymentOrder::STATUS_PAID)
                ->with(['rtdOrder:id,subtotal,commission_percent,commission_amount,gst_percent,gst_amount,total_amount', 'user'])
                ->firstOrFail();

            $invoice = $this->normalizeRtdOrder($order);
        }

        $user = $order->user;

        $invoice['bill_to'] = $user ? $this->billTo($user) : [];
        $invoice['seller']  = [
            'name'       => config('company.name'),
            'legal_name' => config('company.legal_name'),
            'address'    => config('company.address'),
            'gstin'      => config('company.gstin'),
            'email'      => config('company.email'),
            'phone'      => config('company.phone'),
        ];
        $invoice['user'] = $this->userSummary($user);

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

    private function applyAdminFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): void
    {
        if (!empty($filters['date_from'])) {
            $query->whereDate('paid_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('paid_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }
    }

    private function userSummary(?User $user): array
    {
        return [
            'id'           => $user?->id,
            'name'         => $user?->name,
            'company_name' => $user?->company_name,
            'mobile'       => $user?->mobile,
        ];
    }

    /**
     * @param Collection<int, array<string, mixed>> $rows
     * @return array{total_revenue_inr: float, count: int, by_kind: array<string, array{count: int, total_inr: float}>}
     */
    private function buildSummary(Collection $rows): array
    {
        $byKind = [];
        foreach (['credit_pack', 'direct_pay', 'rtd_platform_fee'] as $k) {
            $subset = $rows->where('kind', $k);
            $byKind[$k] = [
                'count'     => $subset->count(),
                'total_inr' => round((float) $subset->sum('total_inr'), 2),
            ];
        }

        return [
            'total_revenue_inr' => round((float) $rows->sum('total_inr'), 2),
            'count'             => $rows->count(),
            'by_kind'           => $byKind,
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
