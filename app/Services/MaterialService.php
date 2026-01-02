<?php

namespace App\Services;

use App\Models\Material;

class MaterialService
{
    public function getMaterials(array $filters = [])
    {
        $query = Material::with('grades');

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->get();
    }
}
