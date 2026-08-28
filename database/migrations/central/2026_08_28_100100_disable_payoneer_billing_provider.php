<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('platform_billing_settings')) {
            return;
        }

        DB::connection('central')
            ->table('platform_billing_settings')
            ->where('provider', 'payoneer')
            ->update([
                'enabled'    => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Payoneer was removed from the product. Do not re-enable.
    }
};
