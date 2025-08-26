<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, save the current payment modes for rollback
        $currentModes = DB::table('payment_modes')->get(['id', 'name'])->toArray();
        $this->currentModes = json_encode($currentModes);
        
        // Update any voucher_payments referencing modes we're going to remove
        // Default to 'cash' for any payment mode that will be removed
        DB::table('voucher_payments')
            ->whereNotIn('payment_mode', ['cash', 'bank transfer', 'adjust in advance', 'fuel(paid to pump)'])
            ->update(['payment_mode' => 'cash']);
            
        // Also update payment_mode_id if it exists
        if (Schema::hasColumn('voucher_payments', 'payment_mode_id')) {
            $cashModeId = DB::table('payment_modes')->where('name', 'cash')->value('id');
            if ($cashModeId) {
                DB::table('voucher_payments')
                    ->whereNotIn('payment_mode', ['cash', 'bank transfer', 'adjust in advance', 'fuel(paid to pump)'])
                    ->update(['payment_mode_id' => $cashModeId]);
            }
        }
        
        // Delete all existing payment modes
        DB::table('payment_modes')->delete();
        
        // Insert only the four specified payment modes
        DB::table('payment_modes')->insert([
            ['name' => 'cash', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'bank transfer', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'adjust in advance', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'fuel(paid to pump)', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete all payment modes
        DB::table('payment_modes')->delete();
        
        // Restore the original payment modes if we have them
        if (isset($this->currentModes)) {
            $modes = json_decode($this->currentModes, true);
            foreach ($modes as $mode) {
                DB::table('payment_modes')->insert([
                    'id' => $mode['id'],
                    'name' => $mode['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            // Fallback: insert the original modes from the create migration
            DB::table('payment_modes')->insert([
                ['name' => 'cash', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'bank transfer', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'cheque', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'upi/qr', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'adjust in advance', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'fuel(paid to pump)', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }
    
    // Property to store current payment modes for rollback
    protected $currentModes;
};
