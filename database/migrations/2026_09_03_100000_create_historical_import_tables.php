<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('seller_id')->nullable();
            $table->boolean('multi_brand')->default(false);
            $table->boolean('enter_live_pipeline')->default(false);
            $table->string('original_filename')->nullable();
            $table->string('stored_path')->nullable();
            $table->string('status', 32)->default('uploaded');
            $table->unsignedInteger('row_count')->default(0);
            $table->json('summary')->nullable();
            $table->json('mapping')->nullable();
            $table->json('decisions')->nullable();
            $table->string('duplicate_strategy', 16)->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamps();
        });

        Schema::create('import_column_maps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('admin_id');
            $table->json('mapping');
            $table->timestamps();
            $table->unique(['tenant_id', 'admin_id']);
        });

        foreach (['leads', 'orders', 'payment_links', 'payments'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'source')) {
                    $blueprint->string('source', 32)->nullable()->index();
                }
                if (! Schema::hasColumn($table, 'import_batch_id')) {
                    $blueprint->unsignedBigInteger('import_batch_id')->nullable()->index();
                }
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
            if (! Schema::hasColumn('payments', 'needs_review')) {
                $table->boolean('needs_review')->default(false);
            }
        });

        Schema::table('payment_links', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_links', 'needs_review')) {
                $table->boolean('needs_review')->default(false);
            }
        });

        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropUnique(['provider', 'provider_payment_intent_id']);
            });
        } catch (\Throwable) {
            try {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropUnique('payments_provider_provider_payment_intent_id_unique');
                });
            } catch (\Throwable) {
                // Index name differs across environments.
            }
        }

        // Cash/check imports must store NULL provider IDs (empty string would collide on unique).
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE payments MODIFY `provider` VARCHAR(255) NULL DEFAULT NULL');
            DB::statement('ALTER TABLE payments MODIFY `provider_payment_intent_id` VARCHAR(255) NULL');
        } else {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('provider')->nullable()->default(null)->change();
                $table->string('provider_payment_intent_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['leads', 'orders', 'payment_links', 'payments'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasColumn($table, 'source')) {
                    $blueprint->dropColumn('source');
                }
                if (Schema::hasColumn($table, 'import_batch_id')) {
                    $blueprint->dropColumn('import_batch_id');
                }
            });
        }

        Schema::dropIfExists('import_column_maps');
        Schema::dropIfExists('import_batches');
    }
};
