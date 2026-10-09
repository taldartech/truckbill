<?php

namespace Tests\Feature\Company;

use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyDigitalSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
        UserRole::create(['id' => 4, 'name' => 'moderator']);
    }

    protected function makeSuperAdmin(): User
    {
        $super = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $company = Company::create([
            'name' => 'Signature Co',
            'gst_slab' => config('payments.gst_slabs')[0],
            'user_id' => $super->id,
            'pan_number' => 'AABAD1452A',
            'gst_scheme' => 'reverse charge',
            'address' => 'Test address',
            'contact_person' => 'Contact',
            'contact_number' => '9876543210',
            'pin_code' => '400001',
            'is_blocked' => false,
        ]);

        $super->update(['company_id' => $company->id]);

        return $super->fresh();
    }

    public function test_super_admin_can_upload_digital_signature_on_company_update(): void
    {
        Storage::fake('public');

        $super = $this->makeSuperAdmin();
        $company = $super->company;
        $file = UploadedFile::fake()->image('signature.png', 200, 80);

        $response = $this->actingAs($super)->put(route('company.update', $company->id), [
            'name' => $company->name,
            'pan_number' => $company->pan_number,
            'gst_scheme' => $company->gst_scheme,
            'address' => $company->address,
            'contact_person' => $company->contact_person,
            'contact_number' => $company->contact_number,
            'pin_code' => $company->pin_code,
            'gst_slab' => $company->gst_slab,
            'digital_signature' => $file,
        ]);

        $response->assertRedirect(route('company.index'));

        $company->refresh();
        $this->assertNotEmpty($company->digital_signature);
        $this->assertTrue(Storage::disk('public')->exists($company->digital_signature));
        $this->assertNotNull($company->signaturePublicUrl());
        $this->assertNotNull($company->signatureFilesystemPathForPdf());
    }

    public function test_print_partials_include_signature_when_present(): void
    {
        Storage::fake('public');

        $super = $this->makeSuperAdmin();
        $company = $super->company;
        $path = UploadedFile::fake()->image('signature.png', 200, 80)
            ->store('company_signatures', 'public');
        $company->update(['digital_signature' => $path]);
        $company->refresh();

        $signatureSrc = $company->signatureFilesystemPathForPdf() ?: $company->signaturePublicUrl();
        $this->assertNotNull($signatureSrc);

        $slotHtml = view('reports.exports.partials.pdf-company-signature-slot', [
            'signatureSrc' => $signatureSrc,
            'companyName' => $company->name,
        ])->render();

        $this->assertStringContainsString('pdf-signature-slot', $slotHtml);
        $this->assertStringContainsString((string) $signatureSrc, $slotHtml);

        $emptyHtml = view('reports.exports.partials.pdf-company-signature-slot', [
            'signatureSrc' => null,
            'companyName' => $company->name,
        ])->render();

        $this->assertStringNotContainsString('pdf-signature-slot', $emptyHtml);
    }

    public function test_invoice_and_bilty_templates_render_signature_markup(): void
    {
        Storage::fake('public');

        $super = $this->makeSuperAdmin();
        $company = $super->company;
        $path = UploadedFile::fake()->image('signature.png', 200, 80)
            ->store('company_signatures', 'public');
        $company->update(['digital_signature' => $path]);

        $truck = (object) [
            'truck_number' => 'MH12AB1234',
            'transporter' => (object) ['pan_number' => null],
        ];

        $lr = (object) [
            'lr_number' => 'LR-SIG-1',
            'gr_date' => now()->toDateString(),
            'truck' => $truck,
            'consignor' => (object) [
                'name' => 'Consignor',
                'contact_number' => null,
                'address' => null,
                'gst_number' => null,
            ],
            'consignee' => (object) [
                'name' => 'Consignee',
                'contact_number' => null,
                'address' => null,
                'gst_number' => null,
            ],
            'freightBookings' => collect(),
            'good_type' => 'Steel',
            'no_of_units' => 1,
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 1000,
            'invoice_number' => null,
            'goods_value' => null,
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'loading_station' => null,
            'unloading_station' => null,
            'lane' => 'Road',
            'eway_bill_number' => null,
            'private_mark' => null,
            'to_pay' => 0,
            'to_be_billed' => 0,
            'paid' => 0,
            'remarks' => null,
        ];

        $biltyHtml = view('reports.exports.partials.lr-copy-bilty-block', [
            'company' => $company,
            'lr' => $lr,
            'copyLabel' => 'CONSIGNOR COPY',
        ])->render();

        $this->assertStringContainsString('pdf-signature-slot', $biltyHtml);

        $invoice = (object) [
            'id' => 1,
            'custom_invoice' => false,
            'lr_ids' => [],
            'invoice_type' => 'Forward',
            'total_amount' => 100,
            'net_amount' => 100,
            'base_amount' => 100,
            'invoice_number' => 'INV-SIG-1',
            'manual_invoice_number' => null,
            'generated_date' => now()->toDateString(),
            'cgst' => 0,
            'sgst' => 0,
            'igst' => 0,
            'consignee' => (object) [
                'name' => 'Customer',
                'gst_number' => null,
                'pan_number' => null,
                'city' => (object) ['name' => 'Mumbai'],
                'state' => (object) ['code' => '27', 'name' => 'Maharashtra'],
            ],
            'company' => $company,
        ];

        $partyBillHtml = view('reports.exports.party-bill', [
            'company' => $company,
            'invoice' => $invoice,
            'customDetails' => collect(),
            'forExcel' => false,
            'partyBillInvoiceUsePdfLogoPath' => false,
            'remark' => '',
            'totalAmount' => 100,
            'totalgst' => 0,
            'cgst' => 0,
            'sgst' => 0,
            'igst' => 0,
        ])->render();

        $this->assertStringContainsString('pdf-signature-slot', $partyBillHtml);
        $this->assertStringContainsString('Authorized Signature', $partyBillHtml);

        foreach ([
            'resources/views/reports/exports/voucher-receipt.blade.php',
            'resources/views/reports/exports/freight-challan-form-b.blade.php',
            'resources/views/reports/exports/party-bill-pdf-modern.blade.php',
        ] as $relativePath) {
            $contents = file_get_contents(base_path($relativePath));
            $this->assertStringContainsString(
                'pdf-company-signature-slot',
                $contents,
                "Expected signature partial include in {$relativePath}"
            );
        }
    }
}
