<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'provider_dispute_id')) {
                $table->string('provider_dispute_id', 64)->nullable()->after('provider_payment_intent_id');
            }
            if (! Schema::hasColumn('payments', 'dispute_status')) {
                $table->string('dispute_status', 24)->nullable()->after('provider_dispute_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'dispute_status')) {
                $table->dropColumn('dispute_status');
            }
            if (Schema::hasColumn('payments', 'provider_dispute_id')) {
                $table->dropColumn('provider_dispute_id');
            }
        });
    }
};
