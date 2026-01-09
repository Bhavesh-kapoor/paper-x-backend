<?php

namespace App\Services;

use App\Models\Material;

class MaterialService
{
    public function getMaterials(array $filters = [])
    {
        $perPage = $filters['per_page'] ?? 50; // Default to 50 for materials list

        $query = Material::with('grades');

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }
}
