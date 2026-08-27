<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('system_announcements')) {
            return;
        }

        Schema::connection('central')->table('system_announcements', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('system_announcements', 'blocks_crm')) {
                $table->boolean('blocks_crm')->default(false)->after('is_dismissible');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::connection('central')->hasTable('system_announcements')) {
            return;
        }

        Schema::connection('central')->table('system_announcements', function (Blueprint $table) {
            if (Schema::connection('central')->hasColumn('system_announcements', 'blocks_crm')) {
                $table->dropColumn('blocks_crm');
            }
        });
    }
};
