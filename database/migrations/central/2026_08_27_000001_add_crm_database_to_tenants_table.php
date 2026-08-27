<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('tenants', 'crm_database')) {
                $table->string('crm_database', 128)->nullable()->after('custom_domain_verified');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            if (Schema::connection('central')->hasColumn('tenants', 'crm_database')) {
                $table->dropColumn('crm_database');
            }
        });
    }
};
