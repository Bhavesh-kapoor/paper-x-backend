<?php

namespace Tests\Unit\Support;

use App\Support\RtdPublicUpload;
use PHPUnit\Framework\TestCase;

class RtdPublicUploadTest extends TestCase
{
    public function test_normalize_product_path_accepts_relative_rtd_products(): void
    {
        $p = 'uploads/rtd/products/abc-uuid.jpg';
        $this->assertSame($p, RtdPublicUpload::normalizeProductImagePathForDb($p));
    }

    public function test_normalize_product_path_accepts_legacy_uploads_images(): void
    {
        $p = 'uploads/images/legacy.jpg';
        $this->assertSame($p, RtdPublicUpload::normalizeProductImagePathForDb($p));
    }

    public function test_normalize_product_path_rejects_unknown_relative(): void
    {
        $this->assertNull(RtdPublicUpload::normalizeProductImagePathForDb('etc/passwd'));
    }

    public function test_normalize_dispatch_accepts_relative(): void
    {
        $p = 'uploads/rtd/dispatch/proof.jpg';
        $this->assertSame($p, RtdPublicUpload::normalizeDispatchFilePathForDb($p));
    }

    public function test_normalize_dispatch_rejects_product_path(): void
    {
        $this->assertNull(RtdPublicUpload::normalizeDispatchFilePathForDb('uploads/rtd/products/x.jpg'));
    }
}
