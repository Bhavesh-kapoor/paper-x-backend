<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\BrandType;
use App\Models\Converter;
use App\Models\ConverterType;
use App\Models\Dealer;
use App\Models\FinishedProduct;
use App\Models\Machine;
use App\Models\MachineDealer;
use App\Models\Material;
use App\Models\ScrapType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegistrationDetailsController extends Controller
{
    public function show(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $role = $user->primary_role;

        $sections = match ($role) {
            'dealer'         => $this->dealerSections($user),
            'machine_dealer',
            'machine-dealer' => $this->machineDealerSections($user),
            'converter'      => $this->converterSections($user),
            'brand'          => $this->brandSections($user),
            default          => $this->genericSections($user),
        };

        return response()->json([
            'role'          => $role,
            'lastUpdatedAt' => ($user->updated_at ?? now())->toIso8601String(),
            'sections'      => $sections,
        ]);
    }

    // ─── Dealer ──────────────────────────────────────────────────────

    protected function dealerSections($user): array
    {
        $dealer = Dealer::with([
            'locations',
            'materials',
            'machines',
            'materialDetails.material',
            'materialDetails.brand',
        ])->where('user_id', $user->id)->first();

        $sections = [];

        // 1. Company overview
        $sections[] = $this->editable([
            'id'    => 'company',
            'title' => 'Company Overview',
            'icon'  => 'Building',
            'rows'  => array_values(array_filter([
                $this->row('company_name', 'Company Name', $user->company_name),
                $this->row('gstin', 'GSTIN', $user->gst_in),
                $this->row('location', 'Location', $this->cityState($user->city, $user->state)),
                $this->row('operation_area', 'Operation Area', $this->formatOperationArea($user->operation_area)),
                $this->row('udyam', 'Udyam Certificate', $user->udyam_certificate ? 'Uploaded' : null),
            ])),
        ], 'company', $this->companyEdit($user));

        // 2. Capacity
        $sections[] = $this->editable([
            'id'    => 'capacity',
            'title' => 'Capacity',
            'icon'  => 'Capacity',
            'rows'  => array_values(array_filter([
                $this->metricRow('capacity_daily', 'Daily Capacity', $dealer->capacity_daily ?? null, $dealer->capacity_unit ?? null),
                $this->metricRow('capacity_monthly', 'Monthly Capacity', $dealer->capacity_monthly ?? null, $dealer->capacity_unit ?? null),
            ])),
        ], 'capacity', [
            'capacity_daily'   => $dealer->capacity_daily ?? null,
            'capacity_monthly' => $dealer->capacity_monthly ?? null,
            'capacity_unit'    => $dealer->capacity_unit ?? null,
        ]);

        // 3. Materials & Grades
        if ($dealer && $dealer->materialDetails->isNotEmpty()) {
            $materialRows = [];
            foreach ($dealer->materialDetails as $i => $detail) {
                $matName   = $detail->material->name ?? 'Unknown';
                $brandName = $detail->brand->name ?? null;
                $agentType = $detail->agent_type ? str_replace('_', ' ', ucwords(strtolower($detail->agent_type), '_')) : null;

                $thicknessStr = '';
                if (is_array($detail->thickness_ranges)) {
                    $parts = [];
                    foreach ($detail->thickness_ranges as $tr) {
                        $parts[] = ($tr['min'] ?? '?') . '–' . ($tr['max'] ?? '?') . ' ' . ($tr['unit'] ?? '');
                    }
                    $thicknessStr = implode(', ', $parts);
                }

                $materialRows[] = [
                    'id'    => "material_{$i}",
                    'type'  => 'detail-card',
                    'label' => $matName,
                    'value' => $brandName ? "Mill: {$brandName}" : '',
                    'chips' => array_values(array_filter([
                        $agentType,
                        $thicknessStr ?: null,
                    ])),
                ];
            }
            $sections[] = [
                'id'    => 'materials',
                'title' => 'Materials & Grades',
                'icon'  => 'Process',
                'rows'  => $materialRows,
            ];
        }

        // 4. Machines (multi-select)
        $sections[] = $this->editable([
            'id'    => 'machines',
            'title' => 'Machines Available',
            'icon'  => 'Settings',
            'rows'  => [$this->chipRow('machine_list', 'Machines', $dealer ? $dealer->machines->pluck('name')->toArray() : [])],
        ], 'machines', $this->msEdit(
            $dealer ? $dealer->machines->pluck('id')->all() : [],
            'machine_ids',
            Machine::orderBy('name')->get(['id', 'name']),
        ));

        // 5. Locations / Warehouses
        if ($dealer && $dealer->locations->isNotEmpty()) {
            $locRows = [];
            foreach ($dealer->locations as $i => $loc) {
                $locRows[] = [
                    'id'    => "location_{$i}",
                    'type'  => 'address',
                    'label' => ucfirst($loc->type ?? 'Location'),
                    'value' => $this->formatAddress($loc->address, $loc->city, $loc->state, $loc->pincode),
                ];
            }
            $sections[] = [
                'id'    => 'locations',
                'title' => 'Warehouses & Locations',
                'icon'  => 'Location',
                'rows'  => $locRows,
            ];
        }

        return $sections;
    }

    // ─── Machine Dealer ──────────────────────────────────────────────

    protected function machineDealerSections($user): array
    {
        $md = MachineDealer::where('user_id', $user->id)->first();
        $sections = [];

        $sections[] = $this->editable([
            'id'    => 'company',
            'title' => 'Company Overview',
            'icon'  => 'Building',
            'rows'  => array_values(array_filter([
                $this->row('company_name', 'Company Name', $md->company_name ?? $user->company_name),
                $this->row('contact_person', 'Contact Person', $md->contact_person_name ?? null),
                $this->row('mobile', 'Mobile', $md->mobile ?? $user->mobile),
                $this->row('email', 'Email', $md->email ?? $user->email),
                $this->row('gstin', 'GST', $md->gst ?? $user->gst_in),
                $this->row('location', 'Location', $this->cityState($md->city ?? $user->city, null)),
            ])),
        ], 'company', $this->companyEdit($user));

        if ($md) {
            $specialization = [];
            if ($md->primary_machine_category) {
                $specialization[] = $this->row('primary_category', 'Primary Category', ucfirst($md->primary_machine_category));
            }
            if (!empty($md->preferred_brand_names)) {
                $specialization[] = [
                    'id'    => 'preferred_brands',
                    'type'  => 'chip-list',
                    'label' => 'Preferred Brands',
                    'chips' => $md->preferred_brand_names,
                ];
            }
            if (!empty($specialization)) {
                $sections[] = [
                    'id'    => 'specialization',
                    'title' => 'Specialization',
                    'icon'  => 'Settings',
                    'rows'  => array_values($specialization),
                ];
            }
        }

        return $sections;
    }

    // ─── Converter ───────────────────────────────────────────────────

    protected function converterSections($user): array
    {
        $conv = Converter::with([
            'converterTypes',
            'finishedProducts',
            'machines',
            'scrapTypes',
            'rawMaterials',
        ])->where('user_id', $user->id)->first();

        $sections = [];

        // 1. Company (common user fields)
        $sections[] = $this->editable([
            'id'    => 'company',
            'title' => 'Company Overview',
            'icon'  => 'Building',
            'rows'  => array_values(array_filter([
                $this->row('company_name', 'Company Name', $user->company_name),
                $this->row('gstin', 'GSTIN', $user->gst_in),
                $this->row('location', 'Location', $this->cityState($user->city, $user->state)),
                $this->row('operation_area', 'Operation Area', $this->formatOperationArea($user->operation_area)),
            ])),
        ], 'company', $this->companyEdit($user));

        // 2. Converter Types (multi-select)
        $typeChips = $conv ? $conv->converterTypes->pluck('name')->toArray() : [];
        if ($conv && $conv->converter_type_custom) {
            $typeChips[] = $conv->converter_type_custom;
        }
        $sections[] = $this->editable([
            'id'    => 'converter_types',
            'title' => 'Converter Type',
            'icon'  => 'Process',
            'rows'  => [$this->chipRow('types_list', 'Types', $typeChips)],
        ], 'converter_types', array_merge(
            $this->msEdit(
                $conv ? $conv->converterTypes->pluck('id')->all() : [],
                'converter_type_ids',
                ConverterType::orderBy('name')->get(['id', 'name']),
            ),
            ['custom' => $conv->converter_type_custom ?? null],
        ));

        // 3. Capacity
        $sections[] = $this->editable([
            'id'    => 'capacity',
            'title' => 'Production Capacity',
            'icon'  => 'Capacity',
            'rows'  => array_values(array_filter([
                $this->metricRow('capacity_daily', 'Daily Capacity', $conv->capacity_daily ?? null, $conv->capacity_unit ?? null),
                $this->metricRow('capacity_monthly', 'Monthly Capacity', $conv->capacity_monthly ?? null, $conv->capacity_unit ?? null),
            ])),
        ], 'capacity', [
            'capacity_daily'   => $conv->capacity_daily ?? null,
            'capacity_monthly' => $conv->capacity_monthly ?? null,
            'capacity_unit'    => $conv->capacity_unit ?? null,
        ]);

        // 4. Machinery
        $sections[] = $this->editable([
            'id'    => 'machines',
            'title' => 'Machinery',
            'icon'  => 'Settings',
            'rows'  => [$this->chipRow('machine_list', 'Machines', $conv ? $conv->machines->pluck('name')->toArray() : [])],
        ], 'machines', $this->msEdit(
            $conv ? $conv->machines->pluck('id')->all() : [],
            'machine_ids',
            Machine::orderBy('name')->get(['id', 'name']),
        ));

        // 5. Finished Products
        $sections[] = $this->editable([
            'id'    => 'finished_products',
            'title' => 'Finished Products',
            'icon'  => 'Finishing',
            'rows'  => [$this->chipRow('products_list', 'Products', $conv ? $conv->finishedProducts->pluck('name')->toArray() : [])],
        ], 'finished_products', $this->msEdit(
            $conv ? $conv->finishedProducts->pluck('id')->all() : [],
            'finished_product_ids',
            FinishedProduct::orderBy('name')->get(['id', 'name']),
        ));

        // 6. Raw Materials
        $sections[] = $this->editable([
            'id'    => 'raw_materials',
            'title' => 'Raw Materials',
            'icon'  => 'Process',
            'rows'  => [$this->chipRow('raw_list', 'Materials', $conv ? $conv->rawMaterials->pluck('name')->toArray() : [])],
        ], 'raw_materials', $this->msEdit(
            $conv ? $conv->rawMaterials->pluck('id')->all() : [],
            'raw_material_ids',
            Material::orderBy('name')->get(['id', 'name']),
        ));

        // 7. Scrap Types
        $sections[] = $this->editable([
            'id'    => 'scrap',
            'title' => 'Scrap Generation',
            'icon'  => 'Scrap',
            'rows'  => [$this->chipRow('scrap_list', 'Scrap Types', $conv ? $conv->scrapTypes->pluck('name')->toArray() : [])],
        ], 'scrap_types', $this->msEdit(
            $conv ? $conv->scrapTypes->pluck('id')->all() : [],
            'scrap_type_ids',
            ScrapType::orderBy('name')->get(['id', 'name']),
        ));

        // 8. Factory Location
        $sections[] = $this->editable([
            'id'    => 'factory',
            'title' => 'Factory Location',
            'icon'  => 'Location',
            'rows'  => [[
                'id'    => 'factory_address',
                'type'  => 'address',
                'label' => 'Factory',
                'value' => $this->formatAddress($conv->factory_address ?? null, $conv->factory_city ?? null, $conv->factory_state ?? null),
            ]],
        ], 'factory', [
            'address'   => $conv->factory_address ?? null,
            'city'      => $conv->factory_city ?? null,
            'state'     => $conv->factory_state ?? null,
            'latitude'  => $conv->factory_latitude ?? null,
            'longitude' => $conv->factory_longitude ?? null,
        ]);

        return $sections;
    }

    // ─── Brand ───────────────────────────────────────────────────────

    protected function brandSections($user): array
    {
        $brand = Brand::with('brandTypes')
            ->where('user_id', $user->id)
            ->first();

        $sections = [];

        // 1. Brand Profile (common)
        $sections[] = $this->editable([
            'id'    => 'brand',
            'title' => 'Brand Profile',
            'icon'  => 'Brand',
            'rows'  => array_values(array_filter([
                $this->row('company_name', 'Company Name', $brand->company_name ?? $user->company_name),
                $this->row('brand_name', 'Brand Name', $brand->brand_name ?? null),
                $this->row('gstin', 'GST', $brand->gst ?? $user->gst_in),
            ])),
        ], 'company', $this->companyEdit($user));

        // 2. Contact (view-only for now)
        $sections[] = [
            'id'    => 'contact',
            'title' => 'Contact Details',
            'icon'  => 'Person',
            'rows'  => array_values(array_filter([
                $this->row('contact_person', 'Contact Person', $brand->contact_person_name ?? null),
                $this->row('mobile', 'Mobile', $brand->mobile ?? $user->mobile),
                $this->row('email', 'Email', $brand->email ?? $user->email),
            ])),
        ];

        // 3. Industries (Brand Types) — multi-select
        $sections[] = $this->editable([
            'id'    => 'industries',
            'title' => 'Industries',
            'icon'  => 'Globe',
            'rows'  => [$this->chipRow('brand_types', 'Operation Industries', $brand ? $brand->brandTypes->pluck('name')->toArray() : [])],
        ], 'industries', $this->msEdit(
            $brand ? $brand->brandTypes->pluck('id')->all() : [],
            'brand_type_ids',
            BrandType::orderBy('name')->get(['id', 'name']),
        ));

        // 4. Location
        $sections[] = $this->editable([
            'id'    => 'location',
            'title' => 'Location',
            'icon'  => 'Location',
            'rows'  => [[
                'id'    => 'address',
                'type'  => 'address',
                'label' => 'Office',
                'value' => $this->formatAddress($brand->address ?? null, $brand->city ?? $user->city, $brand->state ?? $user->state),
            ]],
        ], 'location', [
            'address'   => $brand->address ?? null,
            'city'      => $brand->city ?? $user->city,
            'state'     => $brand->state ?? $user->state,
            'latitude'  => $brand->latitude ?? null,
            'longitude' => $brand->longitude ?? null,
        ]);

        return $sections;
    }

    // ─── Generic fallback ────────────────────────────────────────────

    protected function genericSections($user): array
    {
        return [
            [
                'id'    => 'profile',
                'title' => 'Profile',
                'icon'  => 'Person',
                'rows'  => array_values(array_filter([
                    $this->row('name', 'Name', $user->name),
                    $this->row('mobile', 'Phone', $user->mobile),
                    $this->row('email', 'Email', $user->email),
                    $this->row('company', 'Company', $user->company_name),
                ])),
            ],
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    /**
     * Attach edit metadata (editable + editKey + raw prefill values) to a section.
     */
    private function editable(array $section, string $editKey, array $edit): array
    {
        $section['editable'] = true;
        $section['editKey']  = $editKey;
        $section['edit']     = $edit;

        return $section;
    }

    /** A chip-list row that always renders (even when empty, so users can add). */
    private function chipRow(string $id, string $label, array $chips): array
    {
        return [
            'id'    => $id,
            'type'  => 'chip-list',
            'label' => $label,
            'chips' => array_values($chips),
        ];
    }

    /**
     * Build a multi-select edit block: current selected ids, the payload field to send,
     * and ALL options so the generic editor is role-agnostic.
     */
    private function msEdit(array $selectedIds, string $field, $allModels, string $nameAttr = 'name'): array
    {
        return [
            'selected_ids' => array_values($selectedIds),
            'field'        => $field,
            'options'      => $allModels->map(fn ($m) => ['id' => $m->id, 'name' => $m->{$nameAttr}])->values()->all(),
        ];
    }

    /** Raw common (users-table) fields shared by every role's company/overview section. */
    private function companyEdit($user): array
    {
        return [
            'company_name'   => $user->company_name,
            'gst_in'         => $user->gst_in,
            'operation_area' => $user->operation_area,
            'city'           => $user->city,
            'state'          => $user->state,
        ];
    }

    private function row(string $id, string $label, ?string $value): ?array
    {
        if (!$value || trim($value) === '') {
            return null;
        }

        return [
            'id'    => $id,
            'type'  => 'value',
            'label' => $label,
            'value' => $value,
        ];
    }

    private function metricRow(string $id, string $label, $amount, ?string $unit): ?array
    {
        if (!$amount || (float) $amount <= 0) {
            return null;
        }

        $formatted = number_format((float) $amount, 0) . ($unit ? " {$unit}" : '');

        return [
            'id'    => $id,
            'type'  => 'metric',
            'label' => $label,
            'value' => $formatted,
        ];
    }

    private function cityState(?string $city, ?string $state): ?string
    {
        $parts = collect([$city, $state])->filter()->implode(', ');
        return $parts ?: null;
    }

    private function formatAddress(?string $address, ?string $city, ?string $state, ?string $pincode = null): string
    {
        return collect([$address, $city, $state, $pincode])->filter()->implode(', ') ?: '—';
    }

    private function formatOperationArea(?string $area): ?string
    {
        if (!$area) {
            return null;
        }
        return match (strtolower($area)) {
            'local'     => 'Local',
            'state'     => 'State Level',
            'pan_india', 'pan india' => 'Pan India',
            default     => ucfirst($area),
        };
    }
}
