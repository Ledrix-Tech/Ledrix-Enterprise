<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('tenant_memberships', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('tenant_memberships', 'past_due_at')) {
                $table->timestamp('past_due_at')->nullable()->after('renewal_expired_notice_sent_at');
            }
            if (! Schema::connection('central')->hasColumn('tenant_memberships', 'dunning_notice_0_sent_at')) {
                $table->timestamp('dunning_notice_0_sent_at')->nullable();
            }
            if (! Schema::connection('central')->hasColumn('tenant_memberships', 'dunning_notice_3_sent_at')) {
                $table->timestamp('dunning_notice_3_sent_at')->nullable();
            }
            if (! Schema::connection('central')->hasColumn('tenant_memberships', 'dunning_notice_7_sent_at')) {
                $table->timestamp('dunning_notice_7_sent_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('tenant_memberships', function (Blueprint $table) {
            foreach (['past_due_at', 'dunning_notice_0_sent_at', 'dunning_notice_3_sent_at', 'dunning_notice_7_sent_at'] as $col) {
                if (Schema::connection('central')->hasColumn('tenant_memberships', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
