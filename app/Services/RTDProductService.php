<?php

namespace App\Services;

use App\Exceptions\RTDDomainException;
use App\Models\Material;
use App\Support\RtdPublicUpload;
use App\Models\MaterialFinish;
use App\Models\RtdProduct;
use App\Models\RtdPriceSlab;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RTDProductService
{
    public function __construct(
        protected RtdListingPackService $listingPackService,
    ) {
    }

    public function createProduct(array $data, int $userId): RtdProduct
    {
        if (!$this->listingPackService->canAddProduct($userId)) {
            throw new RTDDomainException(
                'Purchase a listing pack to add RTD products, or your current pack has no slots left or has expired.',
                422
            );
        }

        $this->validatePriceSlabs($data['price_slabs'] ?? []);
        $materialName = $this->resolveMaterialName($data);
        $finishData = $this->resolveFinishData($data);
        $brandingMethods = $this->resolveBrandingMethods($data);

        $imagePath = $this->resolveProductImagePathForStorage($data['image_path'] ?? null);

        return DB::transaction(function () use ($data, $userId, $materialName, $finishData, $brandingMethods, $imagePath) {
            $product = RtdProduct::create([
                'converter_id'    => $userId,
                'category'        => $data['category'],
                'product_name'    => trim((string) ($data['product_name'] ?? '')),
                'image_path'      => $imagePath,
                'size'            => $data['size'] ?? null,
                'size_unit'       => $data['size_unit'] ?? null,
                'material_id'     => $data['material_id'] ?? null,
                'material'        => $materialName,
                'material_custom' => $data['material_custom'] ?? null,
                'thickness'       => $data['thickness'] ?? null,
                'thickness_unit'  => $data['thickness_unit'] ?? null,
                'finish_ids'      => $finishData['ids'],
                'finish'          => $finishData['text'],
                'branding_methods'=> $brandingMethods,
                'branding_method' => !empty($brandingMethods) ? implode(', ', $brandingMethods) : ($data['branding_method'] ?? null),
                'lead_time'       => $data['lead_time'],
                'moq'             => $data['moq'],
                'max_capacity'    => $data['max_capacity'] ?? null,
                'base_price'      => $data['base_price'],
                'buy_now_enabled' => $data['buy_now_enabled'] ?? true,
                'delivery_geography' => $data['delivery_geography'] ?? null,
                'location_id'     => $data['location_id'] ?? null,
                'location_source' => $data['location_source'] ?? null,
                'latitude'        => $data['latitude'] ?? null,
                'longitude'       => $data['longitude'] ?? null,
            ]);

            $this->syncPriceSlabs($product, $data['price_slabs'] ?? []);

            $this->listingPackService->incrementUsedCount($userId);

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
        $materialName = $this->resolveMaterialName($data);
        $finishData = $this->resolveFinishData($data);
        $brandingMethods = $this->resolveBrandingMethods($data);

        return DB::transaction(function () use ($product, $data, $materialName, $finishData, $brandingMethods) {
            $updatePayload = [];
            $directFields = [
                'category',
                'product_name',
                'image_path',
                'size',
                'size_unit',
                'material_id',
                'material_custom',
                'thickness',
                'thickness_unit',
                'lead_time',
                'moq',
                'max_capacity',
                'base_price',
                'buy_now_enabled',
                'delivery_geography',
                'location_id',
                'location_source',
                'latitude',
                'longitude',
            ];
            foreach ($directFields as $field) {
                if (array_key_exists($field, $data)) {
                    $value = $data[$field];
                    if ($field === 'product_name') {
                        $value = trim((string) ($value ?? ''));
                    }
                    if ($field === 'image_path') {
                        $value = $this->resolveProductImagePathForStorage(
                            is_string($value) || $value === null ? $value : null
                        );
                    }
                    $updatePayload[$field] = $value;
                }
            }
            if (array_key_exists('material', $data) || array_key_exists('material_custom', $data) || array_key_exists('material_id', $data)) {
                $updatePayload['material'] = $materialName;
            }
            if (array_key_exists('finish_ids', $data) || array_key_exists('finish', $data)) {
                $updatePayload['finish_ids'] = $finishData['ids'];
                $updatePayload['finish'] = $finishData['text'];
            }
            if (array_key_exists('branding_methods', $data) || array_key_exists('branding_method', $data)) {
                $updatePayload['branding_methods'] = $brandingMethods;
                $updatePayload['branding_method'] = !empty($brandingMethods)
                    ? implode(', ', $brandingMethods)
                    : null;
            }

            $product->update($updatePayload);

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
        $query = RtdProduct::visible()->with('priceSlabs', 'converter');

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['lead_time'])) {
            $query->where('lead_time', $filters['lead_time']);
        }

        if (!empty($filters['delivery_geography'])) {
            $query->where('delivery_geography', 'LIKE', '%' . $filters['delivery_geography'] . '%');
        }

        if (!empty($filters['min_price'])) {
            $query->where('base_price', '>=', (float) $filters['min_price']);
        }
        if (!empty($filters['max_price'])) {
            $query->where('base_price', '<=', (float) $filters['max_price']);
        }

        if (!empty($filters['min_moq'])) {
            $query->where('moq', '>=', (int) $filters['min_moq']);
        }
        if (!empty($filters['max_moq'])) {
            $quantity = (int) $filters['max_moq'];
            $query->where('moq', '<=', $quantity);
            // Only show products that can fulfill this quantity (max_capacity >= quantity or unlimited)
            $query->where(function ($q) use ($quantity) {
                $q->whereNull('max_capacity')
                  ->orWhere('max_capacity', '>=', $quantity);
            });
        }

        if (isset($filters['has_branding'])) {
            if ($filters['has_branding'] === 'yes' || $filters['has_branding'] === '1') {
                $query->whereNotNull('branding_methods')
                      ->where('branding_methods', '!=', '[]');
            } elseif ($filters['has_branding'] === 'no' || $filters['has_branding'] === '0') {
                $query->where(function ($q) {
                    $q->whereNull('branding_methods')
                      ->orWhere('branding_methods', '[]');
                });
            }
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

    private function resolveMaterialName(array $data): ?string
    {
        if (!empty($data['material'])) {
            return trim((string) $data['material']);
        }

        if (!empty($data['material_custom'])) {
            return trim((string) $data['material_custom']);
        }

        if (!empty($data['material_id'])) {
            $material = Material::find((int) $data['material_id']);
            return $material?->name;
        }

        return null;
    }

    private function resolveFinishData(array $data): array
    {
        if (!empty($data['finish_ids']) && is_array($data['finish_ids'])) {
            $ids = array_values(array_map('intval', $data['finish_ids']));
            $names = MaterialFinish::whereIn('id', $ids)->pluck('name')->toArray();
            return [
                'ids' => $ids,
                'text' => !empty($names) ? implode(', ', $names) : ($data['finish'] ?? null),
            ];
        }

        if (!empty($data['finish'])) {
            return [
                'ids' => null,
                'text' => trim((string) $data['finish']),
            ];
        }

        return [
            'ids' => null,
            'text' => null,
        ];
    }

    private function resolveBrandingMethods(array $data): ?array
    {
        if (!empty($data['branding_methods']) && is_array($data['branding_methods'])) {
            return array_values(array_slice($data['branding_methods'], 0, 2));
        }

        if (!empty($data['branding_method'])) {
            return array_values(
                array_slice(
                    array_filter(array_map('trim', explode(',', (string) $data['branding_method']))),
                    0,
                    2
                )
            );
        }

        return null;
    }

    private function resolveProductImagePathForStorage(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $normalized = RtdPublicUpload::normalizeProductImagePathForDb(trim($raw));
        if ($normalized === null) {
            throw ValidationException::withMessages(['image_path' => ['Invalid image path.']]);
        }

        return $normalized;
    }
}
