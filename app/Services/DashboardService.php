<?php

namespace App\Services;

use App\Services\DealerService;
use App\Services\MachineDealerService;
use App\Services\ConverterService;
use App\Services\BrandService;

class DashboardService
{
    public function __construct(
        protected DealerService $dealerService,
        protected MachineDealerService $machineDealerService,
        protected ConverterService $converterService,
        protected BrandService $brandService
    ) {
    }

    public function getDashboard(int $userId, string $role): array
    {
        return match (strtolower($role)) {
            'dealer' => $this->dealerService->getDashboard($userId),
            'machine-dealer' => $this->machineDealerService->getDashboard($userId),
            'converter' => $this->converterService->getDashboard($userId),
            'brand' => $this->brandService->getDashboard($userId),
            default => throw new \Exception('Invalid role: ' . $role, 400),
        };
    }
}

