<?php

use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('template_name')->nullable();
            $table->string('template_address')->nullable();
            $table->string('template_header')->nullable();
            $table->text('template_note')->nullable();
            $table->string('template_jurisdictional_boundary')->nullable();
            $table->string('invoice_template')->default('classic');
            $table->string('lr_copy_template')->default('classic');
            $table->string('freight_challan_template')->default('classic');
            $table->timestamps();
        });

        Company::withTrashed()->orderBy('id')->chunkById(100, function ($companies): void {
            foreach ($companies as $company) {
                $attributes = $company->getAttributes();
                CompanySetting::query()->create([
                    'company_id' => $company->id,
                    'template_name' => $attributes['template_name'] ?? $company->name,
                    'template_address' => $attributes['template_address'] ?? $company->address,
                    'template_header' => $attributes['template_header'] ?? null,
                    'template_note' => $attributes['template_note'] ?? null,
                    'template_jurisdictional_boundary' => $attributes['template_jurisdictional_boundary'] ?? null,
                    'invoice_template' => $attributes['invoice_template'] ?? 'classic',
                    'lr_copy_template' => $attributes['lr_copy_template'] ?? 'classic',
                    'freight_challan_template' => $attributes['freight_challan_template'] ?? 'classic',
                ]);
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'template_jurisdictional_boundary',
                'template_header',
                'template_name',
                'template_address',
                'template_note',
                'invoice_template',
                'lr_copy_template',
                'freight_challan_template',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('template_jurisdictional_boundary')->nullable();
            $table->string('template_header')->nullable();
            $table->string('template_name')->nullable();
            $table->string('template_address')->nullable();
            $table->text('template_note')->nullable();
            $table->string('invoice_template')->default('classic');
            $table->string('lr_copy_template')->default('classic');
            $table->string('freight_challan_template')->default('classic');
        });

        CompanySetting::query()->orderBy('id')->chunkById(100, function ($settings): void {
            foreach ($settings as $setting) {
                Company::withTrashed()->where('id', $setting->company_id)->update([
                    'template_name' => $setting->template_name,
                    'template_address' => $setting->template_address,
                    'template_header' => $setting->template_header,
                    'template_note' => $setting->template_note,
                    'template_jurisdictional_boundary' => $setting->template_jurisdictional_boundary,
                    'invoice_template' => $setting->invoice_template,
                    'lr_copy_template' => $setting->lr_copy_template,
                    'freight_challan_template' => $setting->freight_challan_template,
                ]);
            }
        });

        Schema::dropIfExists('company_settings');
    }
};
