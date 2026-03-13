<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Converter;
use App\Models\Dealer;
use App\Models\MachineDealer;
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
        $sections[] = [
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
        ];

        // 2. Capacity
        if ($dealer) {
            $sections[] = [
                'id'    => 'capacity',
                'title' => 'Capacity',
                'icon'  => 'Capacity',
                'rows'  => array_values(array_filter([
                    $this->metricRow('capacity_daily', 'Daily Capacity', $dealer->capacity_daily, $dealer->capacity_unit),
                    $this->metricRow('capacity_monthly', 'Monthly Capacity', $dealer->capacity_monthly, $dealer->capacity_unit),
                ])),
            ];
        }

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

        // 4. Machines
        if ($dealer && $dealer->machines->isNotEmpty()) {
            $sections[] = [
                'id'    => 'machines',
                'title' => 'Machines Available',
                'icon'  => 'Settings',
                'rows'  => [
                    [
                        'id'    => 'machine_list',
                        'type'  => 'chip-list',
                        'label' => 'Machines',
                        'chips' => $dealer->machines->pluck('name')->toArray(),
                    ],
                ],
            ];
        }

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

        $sections[] = [
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
        ];

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

        // 1. Company
        $sections[] = [
            'id'    => 'company',
            'title' => 'Company Overview',
            'icon'  => 'Building',
            'rows'  => array_values(array_filter([
                $this->row('company_name', 'Company Name', $user->company_name),
                $this->row('gstin', 'GSTIN', $user->gst_in),
                $this->row('location', 'Location', $this->cityState($user->city, $user->state)),
                $this->row('operation_area', 'Operation Area', $this->formatOperationArea($user->operation_area)),
            ])),
        ];

        if (!$conv) {
            return $sections;
        }

        // 2. Converter Types
        if ($conv->converterTypes->isNotEmpty()) {
            $chips = $conv->converterTypes->pluck('name')->toArray();
            if ($conv->converter_type_custom) {
                $chips[] = $conv->converter_type_custom;
            }
            $sections[] = [
                'id'    => 'converter_types',
                'title' => 'Converter Type',
                'icon'  => 'Process',
                'rows'  => [
                    [
                        'id'    => 'types_list',
                        'type'  => 'chip-list',
                        'label' => 'Types',
                        'chips' => $chips,
                    ],
                ],
            ];
        }

        // 3. Capacity
        $sections[] = [
            'id'    => 'capacity',
            'title' => 'Production Capacity',
            'icon'  => 'Capacity',
            'rows'  => array_values(array_filter([
                $this->metricRow('capacity_daily', 'Daily Capacity', $conv->capacity_daily, $conv->capacity_unit),
                $this->metricRow('capacity_monthly', 'Monthly Capacity', $conv->capacity_monthly, $conv->capacity_unit),
            ])),
        ];

        // 4. Machinery
        if ($conv->machines->isNotEmpty()) {
            $sections[] = [
                'id'    => 'machines',
                'title' => 'Machinery',
                'icon'  => 'Settings',
                'rows'  => [
                    [
                        'id'    => 'machine_list',
                        'type'  => 'chip-list',
                        'label' => 'Machines',
                        'chips' => $conv->machines->pluck('name')->toArray(),
                    ],
                ],
            ];
        }

        // 5. Finished Products
        if ($conv->finishedProducts->isNotEmpty()) {
            $sections[] = [
                'id'    => 'finished_products',
                'title' => 'Finished Products',
                'icon'  => 'Finishing',
                'rows'  => [
                    [
                        'id'    => 'products_list',
                        'type'  => 'chip-list',
                        'label' => 'Products',
                        'chips' => $conv->finishedProducts->pluck('name')->toArray(),
                    ],
                ],
            ];
        }

        // 6. Raw Materials
        if ($conv->rawMaterials->isNotEmpty()) {
            $sections[] = [
                'id'    => 'raw_materials',
                'title' => 'Raw Materials',
                'icon'  => 'Process',
                'rows'  => [
                    [
                        'id'    => 'raw_list',
                        'type'  => 'chip-list',
                        'label' => 'Materials',
                        'chips' => $conv->rawMaterials->pluck('name')->toArray(),
                    ],
                ],
            ];
        }

        // 7. Scrap Types
        if ($conv->scrapTypes->isNotEmpty()) {
            $sections[] = [
                'id'    => 'scrap',
                'title' => 'Scrap Generation',
                'icon'  => 'Scrap',
                'rows'  => [
                    [
                        'id'    => 'scrap_list',
                        'type'  => 'chip-list',
                        'label' => 'Scrap Types',
                        'chips' => $conv->scrapTypes->pluck('name')->toArray(),
                    ],
                ],
            ];
        }

        // 8. Factory Location
        if ($conv->factory_address || $conv->factory_city) {
            $sections[] = [
                'id'    => 'factory',
                'title' => 'Factory Location',
                'icon'  => 'Location',
                'rows'  => [
                    [
                        'id'    => 'factory_address',
                        'type'  => 'address',
                        'label' => 'Factory',
                        'value' => $this->formatAddress($conv->factory_address, $conv->factory_city, $conv->factory_state),
                    ],
                ],
            ];
        }

        return $sections;
    }

    // ─── Brand ───────────────────────────────────────────────────────

    protected function brandSections($user): array
    {
        $brand = Brand::with('brandTypes')
            ->where('user_id', $user->id)
            ->first();

        $sections = [];

        // 1. Brand Profile
        $sections[] = [
            'id'    => 'brand',
            'title' => 'Brand Profile',
            'icon'  => 'Brand',
            'rows'  => array_values(array_filter([
                $this->row('company_name', 'Company Name', $brand->company_name ?? $user->company_name),
                $this->row('brand_name', 'Brand Name', $brand->brand_name ?? null),
                $this->row('gstin', 'GST', $brand->gst ?? $user->gst_in),
            ])),
        ];

        // 2. Contact
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

        // 3. Industries (Brand Types)
        if ($brand && $brand->brandTypes->isNotEmpty()) {
            $sections[] = [
                'id'    => 'industries',
                'title' => 'Industries',
                'icon'  => 'Globe',
                'rows'  => [
                    [
                        'id'    => 'brand_types',
                        'type'  => 'chip-list',
                        'label' => 'Operation Industries',
                        'chips' => $brand->brandTypes->pluck('name')->toArray(),
                    ],
                ],
            ];
        }

        // 4. Location
        if ($brand) {
            $sections[] = [
                'id'    => 'location',
                'title' => 'Location',
                'icon'  => 'Location',
                'rows'  => [
                    [
                        'id'    => 'address',
                        'type'  => 'address',
                        'label' => 'Office',
                        'value' => $this->formatAddress($brand->address, $brand->city ?? $user->city, $brand->state ?? $user->state),
                    ],
                ],
            ];
        }

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
