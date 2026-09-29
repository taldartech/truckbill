<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Account Groups (Hierarchical Chart of Accounts)
        if (! Schema::hasTable('account_groups')) {
            Schema::create('account_groups', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('account_groups')->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->enum('primary_type', ['asset', 'liability', 'equity', 'income', 'expense']);
                $table->boolean('is_builtin')->default(false);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['company_id', 'primary_type']);
                $table->index(['company_id', 'parent_id']);
            });
        }

        // 2. Ledgers (General Ledger Accounts)
        if (! Schema::hasTable('ledgers')) {
            Schema::create('ledgers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('account_group_id')->constrained('account_groups')->restrictOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->decimal('opening_balance', 15, 2)->default(0.00);
                $table->enum('opening_balance_type', ['debit', 'credit'])->default('debit');
                $table->date('opening_balance_date')->nullable();
                $table->string('opening_balance_reference', 100)->nullable();
                $table->decimal('current_balance', 15, 2)->default(0.00);
                $table->string('entity_type')->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->string('gstin', 20)->nullable();
                $table->string('pan_number', 15)->nullable();
                $table->string('tds_section', 20)->nullable();
                $table->decimal('tds_rate', 5, 2)->nullable();
                $table->boolean('is_builtin')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['company_id', 'account_group_id']);
                $table->index(['company_id', 'entity_type', 'entity_id']);
                $table->index(['company_id', 'name']);
            });
        }

        // 3. Cost Centres (Truck, Trip/LR, Branch)
        if (! Schema::hasTable('cost_centres')) {
            Schema::create('cost_centres', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->enum('category', ['truck', 'trip', 'branch', 'other'])->default('other');
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('entity_type')->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['company_id', 'category']);
                $table->index(['company_id', 'entity_type', 'entity_id']);
            });
        }

        // 4. Accounting Vouchers (Tally Voucher Types)
        if (! Schema::hasTable('accounting_vouchers')) {
            Schema::create('accounting_vouchers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->enum('voucher_type', [
                    'sales',
                    'purchase',
                    'payment',
                    'receipt',
                    'journal',
                    'contra',
                    'debit_note',
                    'credit_note',
                ]);
                $table->string('voucher_number');
                $table->date('voucher_date');
                $table->string('reference_number')->nullable();
                $table->date('reference_date')->nullable();
                $table->text('narration')->nullable();
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->decimal('total_amount', 15, 2)->default(0.00);
                $table->boolean('is_posted')->default(true);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['company_id', 'voucher_type', 'voucher_date']);
                $table->index(['company_id', 'voucher_number']);
                $table->index(['source_type', 'source_id']);
            });
        }

        // 5. Accounting Voucher Items (Double Entry Lines: Debit / Credit)
        if (! Schema::hasTable('accounting_voucher_items')) {
            Schema::create('accounting_voucher_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('accounting_voucher_id')->constrained('accounting_vouchers')->cascadeOnDelete();
                $table->foreignId('ledger_id')->constrained('ledgers')->restrictOnDelete();
                $table->enum('type', ['debit', 'credit']);
                $table->decimal('amount', 15, 2);
                $table->foreignId('cost_centre_id')->nullable()->constrained('cost_centres')->nullOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->string('particulars_note')->nullable();
                $table->date('reconciliation_date')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'ledger_id']);
                $table->index(['accounting_voucher_id', 'type']);
                $table->index(['company_id', 'cost_centre_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_voucher_items');
        Schema::dropIfExists('accounting_vouchers');
        Schema::dropIfExists('cost_centres');
        Schema::dropIfExists('ledgers');
        Schema::dropIfExists('account_groups');
    }
};
