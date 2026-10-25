<?php

namespace Tests\Unit\Support;

use App\Support\FreightChallanTemplate;
use App\Support\LrCopyPdfTemplate;
use App\Support\MpdfDocument;
use Tests\TestCase;

class TransportPdfTemplatesTest extends TestCase
{
    // ==========================================
    // LrCopyPdfTemplate Tests
    // ==========================================

    public function test_lr_copy_normalize_key_maps_classic_variants(): void
    {
        $this->assertSame('classic', LrCopyPdfTemplate::normalizeKey('default'));
        $this->assertSame('classic', LrCopyPdfTemplate::normalizeKey('a'));
        $this->assertSame('classic', LrCopyPdfTemplate::normalizeKey('classic'));
    }

    public function test_lr_copy_normalize_key_maps_modern_variants(): void
    {
        $this->assertSame('modern', LrCopyPdfTemplate::normalizeKey('modern'));
        $this->assertSame('modern', LrCopyPdfTemplate::normalizeKey('b'));
        $this->assertSame('modern', LrCopyPdfTemplate::normalizeKey('modern_b'));
        $this->assertSame('modern', LrCopyPdfTemplate::normalizeKey('modern_a'));
    }

    public function test_lr_copy_normalize_key_maps_compact_variants(): void
    {
        $this->assertSame('compact', LrCopyPdfTemplate::normalizeKey('compact'));
        $this->assertSame('compact', LrCopyPdfTemplate::normalizeKey('c'));
        $this->assertSame('compact', LrCopyPdfTemplate::normalizeKey('modern_c'));
    }

    public function test_lr_copy_normalize_key_defaults_to_classic_for_null_empty_or_unknown(): void
    {
        $this->assertSame('classic', LrCopyPdfTemplate::normalizeKey(null));
        $this->assertSame('classic', LrCopyPdfTemplate::normalizeKey(''));
        $this->assertSame('classic', LrCopyPdfTemplate::normalizeKey('unknown_template'));
        $this->assertSame('classic', LrCopyPdfTemplate::normalizeKey('xyz'));
    }

    public function test_lr_copy_view_name_returns_expected_template_paths(): void
    {
        $this->assertSame('reports.exports.lr-copy-modern-a', LrCopyPdfTemplate::viewName('modern'));
        $this->assertSame('reports.exports.lr-copy-modern-c', LrCopyPdfTemplate::viewName('compact'));
        $this->assertSame('reports.exports.lr_copy', LrCopyPdfTemplate::viewName('classic'));
        $this->assertSame('reports.exports.lr_copy', LrCopyPdfTemplate::viewName('default'));
        $this->assertSame('reports.exports.lr_copy', LrCopyPdfTemplate::viewName('unknown'));
    }

    // ==========================================
    // FreightChallanTemplate Tests
    // ==========================================

    public function test_freight_challan_normalize_key_maps_classic_variants(): void
    {
        $this->assertSame('classic', FreightChallanTemplate::normalizeKey('default'));
        $this->assertSame('classic', FreightChallanTemplate::normalizeKey('a'));
        $this->assertSame('classic', FreightChallanTemplate::normalizeKey('classic'));
        $this->assertSame('classic', FreightChallanTemplate::normalizeKey('voucher'));
    }

    public function test_freight_challan_normalize_key_maps_detailed_variants(): void
    {
        $this->assertSame('detailed', FreightChallanTemplate::normalizeKey('modern'));
        $this->assertSame('detailed', FreightChallanTemplate::normalizeKey('b'));
        $this->assertSame('detailed', FreightChallanTemplate::normalizeKey('detailed'));
        $this->assertSame('detailed', FreightChallanTemplate::normalizeKey('challan_b'));
        $this->assertSame('detailed', FreightChallanTemplate::normalizeKey('form_b'));
    }

    public function test_freight_challan_normalize_key_defaults_to_classic_for_null_empty_or_unknown(): void
    {
        $this->assertSame('classic', FreightChallanTemplate::normalizeKey(null));
        $this->assertSame('classic', FreightChallanTemplate::normalizeKey(''));
        $this->assertSame('classic', FreightChallanTemplate::normalizeKey('unknown'));
        $this->assertSame('classic', FreightChallanTemplate::normalizeKey('xyz_invalid'));
    }

    public function test_freight_challan_view_name_returns_expected_template_paths(): void
    {
        $this->assertSame('reports.exports.freight-challan-form-b', FreightChallanTemplate::viewName('detailed'));
        $this->assertSame('reports.exports.voucher-receipt', FreightChallanTemplate::viewName('classic'));
        $this->assertSame('reports.exports.voucher-receipt', FreightChallanTemplate::viewName('default'));
        $this->assertSame('reports.exports.voucher-receipt', FreightChallanTemplate::viewName('unknown'));
    }

    // ==========================================
    // MpdfDocument Tests
    // ==========================================

    public function test_mpdf_document_creates_temp_dir_when_missing_and_applies_overrides(): void
    {
        $tempDir = storage_path('app/mpdf');

        if (is_dir($tempDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $fileinfo) {
                $fileinfo->isDir() ? @rmdir($fileinfo->getRealPath()) : @unlink($fileinfo->getRealPath());
            }
            @rmdir($tempDir);
        }

        $mpdf = MpdfDocument::make([
            'format' => 'Legal',
            'orientation' => 'L',
        ]);

        $this->assertInstanceOf(\Mpdf\Mpdf::class, $mpdf);
        $this->assertTrue(is_dir($tempDir));
    }
}
