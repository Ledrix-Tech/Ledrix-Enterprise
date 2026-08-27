<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        if (! Schema::connection('central')->hasColumn('tenant_usage_snapshots', 'storage_alert_80_sent_at')) {
            Schema::connection('central')->table('tenant_usage_snapshots', function (Blueprint $table) {
                $table->timestamp('storage_alert_80_sent_at')->nullable()->after('storage_used_mb');
            });
        }

        if (! Schema::connection('central')->hasColumn('tenant_usage_snapshots', 'storage_alert_100_sent_at')) {
            Schema::connection('central')->table('tenant_usage_snapshots', function (Blueprint $table) {
                $table->timestamp('storage_alert_100_sent_at')->nullable()->after('storage_alert_80_sent_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('central')->hasColumn('tenant_usage_snapshots', 'storage_alert_100_sent_at')) {
            Schema::connection('central')->table('tenant_usage_snapshots', function (Blueprint $table) {
                $table->dropColumn('storage_alert_100_sent_at');
            });
        }

        if (Schema::connection('central')->hasColumn('tenant_usage_snapshots', 'storage_alert_80_sent_at')) {
            Schema::connection('central')->table('tenant_usage_snapshots', function (Blueprint $table) {
                $table->dropColumn('storage_alert_80_sent_at');
            });
        }
    }
};
