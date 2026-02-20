<?php

namespace App\Services;

use App\Exceptions\RTDDomainException;
use App\Models\RtdProduct;
use App\Models\RtdPriceSlab;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RTDProductService
{
    public function createProduct(array $data, int $userId): RtdProduct
    {
        $this->validatePriceSlabs($data['price_slabs'] ?? []);

        return DB::transaction(function () use ($data, $userId) {
            $product = RtdProduct::create([
                'converter_id'    => $userId,
                'category'        => $data['category'],
                'product_name'    => $data['product_name'],
                'image_path'      => $data['image_path'] ?? null,
                'size'            => $data['size'] ?? null,
                'material'        => $data['material'] ?? null,
                'gsm'             => $data['gsm'] ?? null,
                'finish'          => $data['finish'] ?? null,
                'branding_method' => $data['branding_method'] ?? null,
                'lead_time'       => $data['lead_time'],
                'moq'             => $data['moq'],
                'max_capacity'    => $data['max_capacity'] ?? null,
                'base_price'      => $data['base_price'],
                'buy_now_enabled' => $data['buy_now_enabled'] ?? true,
                'delivery_geography' => $data['delivery_geography'] ?? null,
            ]);

            $this->syncPriceSlabs($product, $data['price_slabs'] ?? []);

            return $product->load('priceSlabs');
        });
    }

    public function updateProduct(int $productId, array $data, int $userId): RtdProduct
    {
        $product = RtdProduct::where('id', $productId)
            ->where('converter_id', $userId)
            ->firstOrFail();

        if (isset($data['price_slabs'])) {
            $this->validatePriceSlabs($data['price_slabs']);
        }

        return DB::transaction(function () use ($product, $data) {
            $product->update(array_filter([
                'category'        => $data['category'] ?? null,
                'product_name'    => $data['product_name'] ?? null,
                'image_path'      => $data['image_path'] ?? null,
                'size'            => $data['size'] ?? null,
                'material'        => $data['material'] ?? null,
                'gsm'             => $data['gsm'] ?? null,
                'finish'          => $data['finish'] ?? null,
                'branding_method' => $data['branding_method'] ?? null,
                'lead_time'       => $data['lead_time'] ?? null,
                'moq'             => $data['moq'] ?? null,
                'max_capacity'    => $data['max_capacity'] ?? null,
                'base_price'      => $data['base_price'] ?? null,
                'buy_now_enabled' => $data['buy_now_enabled'] ?? null,
                'delivery_geography' => $data['delivery_geography'] ?? null,
            ], fn ($v) => $v !== null));

            if (isset($data['price_slabs'])) {
                $this->syncPriceSlabs($product, $data['price_slabs']);
            }

            return $product->fresh('priceSlabs');
        });
    }

    public function pauseProduct(int $productId, int $userId): RtdProduct
    {
        $product = RtdProduct::where('id', $productId)
            ->where('converter_id', $userId)
            ->firstOrFail();

        if ($product->status === 'inactive') {
            throw new RTDDomainException('Cannot pause an inactive product');
        }

        $product->update(['status' => 'paused']);

        return $product->fresh();
    }

    public function resumeProduct(int $productId, int $userId): RtdProduct
    {
        $product = RtdProduct::where('id', $productId)
            ->where('converter_id', $userId)
            ->firstOrFail();

        if ($product->status === 'inactive') {
            throw new RTDDomainException('Cannot resume an inactive product');
        }

        $product->update(['status' => 'active']);

        return $product->fresh();
    }

    public function incrementDecline(RtdProduct $product): void
    {
        $product->increment('decline_count');

        if ($product->decline_count >= 3) {
            $product->decrement('visibility_score', 20);
        }

        if ($product->decline_count >= 5) {
            $product->update(['status' => 'inactive']);
            return;
        }

        $product->update(['status' => 'paused']);
    }

    public function rewardCompletion(RtdProduct $product): void
    {
        if ($product->decline_count > 0) {
            $product->decrement('decline_count');
        }

        $newScore = min(100, $product->visibility_score + 10);
        $product->update(['visibility_score' => $newScore]);
    }

    public function getConverterProducts(int $userId, array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = RtdProduct::where('converter_id', $userId)
            ->with('priceSlabs');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function browseCatalog(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = RtdProduct::visible()->with('priceSlabs');

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['lead_time'])) {
            $query->where('lead_time', $filters['lead_time']);
        }

        if (!empty($filters['delivery_geography'])) {
            $query->where('delivery_geography', 'LIKE', '%' . $filters['delivery_geography'] . '%');
        }

        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortDir   = $filters['sort_dir'] ?? 'desc';
        $allowed   = ['base_price', 'created_at', 'visibility_score'];

        if (in_array($sortField, $allowed, true)) {
            $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    // ── Private helpers ──

    private function validatePriceSlabs(array $slabs): void
    {
        if (empty($slabs)) {
            return;
        }

        foreach ($slabs as $i => $slab) {
            if (($slab['min_qty'] ?? 0) >= ($slab['max_qty'] ?? 0)) {
                throw ValidationException::withMessages([
                    "price_slabs.{$i}" => "min_qty must be less than max_qty",
                ]);
            }
        }

        usort($slabs, fn ($a, $b) => $a['min_qty'] <=> $b['min_qty']);

        for ($i = 1; $i < count($slabs); $i++) {
            if ($slabs[$i]['min_qty'] <= $slabs[$i - 1]['max_qty']) {
                throw ValidationException::withMessages([
                    'price_slabs' => "Slab ranges overlap: [{$slabs[$i - 1]['min_qty']}-{$slabs[$i - 1]['max_qty']}] and [{$slabs[$i]['min_qty']}-{$slabs[$i]['max_qty']}]",
                ]);
            }
        }
    }

    private function syncPriceSlabs(RtdProduct $product, array $slabs): void
    {
        $product->priceSlabs()->delete();

        foreach ($slabs as $slab) {
            $product->priceSlabs()->create([
                'min_qty'        => $slab['min_qty'],
                'max_qty'        => $slab['max_qty'],
                'price_per_unit' => $slab['price_per_unit'],
            ]);
        }
    }
}
