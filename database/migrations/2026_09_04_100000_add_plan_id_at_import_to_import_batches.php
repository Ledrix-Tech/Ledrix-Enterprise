<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('import_batches', 'plan_id_at_import')) {
                $table->unsignedBigInteger('plan_id_at_import')->nullable()->after('admin_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            if (Schema::hasColumn('import_batches', 'plan_id_at_import')) {
                $table->dropColumn('plan_id_at_import');
            }
        });
    }
};
