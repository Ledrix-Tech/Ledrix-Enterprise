<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->table('package_pricings', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('package_pricings', 'import_max_rows_per_upload')) {
                $table->integer('import_max_rows_per_upload')->default(150);
            }
            if (! Schema::connection('central')->hasColumn('package_pricings', 'import_max_uploads_per_month')) {
                $table->integer('import_max_uploads_per_month')->default(1);
            }
            if (! Schema::connection('central')->hasColumn('package_pricings', 'import_multi_brand_allowed')) {
                $table->boolean('import_multi_brand_allowed')->default(false);
            }
            if (! Schema::connection('central')->hasColumn('package_pricings', 'import_reimport_allowed')) {
                $table->boolean('import_reimport_allowed')->default(false);
            }
        });

        Schema::connection('central')->table('tenant_limit_overrides', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('tenant_limit_overrides', 'import_max_rows_per_upload')) {
                $table->integer('import_max_rows_per_upload')->nullable();
            }
            if (! Schema::connection('central')->hasColumn('tenant_limit_overrides', 'import_max_uploads_per_month')) {
                $table->integer('import_max_uploads_per_month')->nullable();
            }
            if (! Schema::connection('central')->hasColumn('tenant_limit_overrides', 'import_multi_brand_allowed')) {
                $table->integer('import_multi_brand_allowed')->nullable();
            }
            if (! Schema::connection('central')->hasColumn('tenant_limit_overrides', 'import_reimport_allowed')) {
                $table->integer('import_reimport_allowed')->nullable();
            }
        });

        Schema::connection('central')->table('tenant_usage_snapshots', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('tenant_usage_snapshots', 'imports_this_month')) {
                $table->unsignedInteger('imports_this_month')->default(0);
            }
        });

        $this->seedPlanDefaults();
    }

    public function down(): void
    {
        Schema::connection('central')->table('package_pricings', function (Blueprint $table) {
            foreach (['import_max_rows_per_upload', 'import_max_uploads_per_month', 'import_multi_brand_allowed', 'import_reimport_allowed'] as $column) {
                if (Schema::connection('central')->hasColumn('package_pricings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::connection('central')->table('tenant_limit_overrides', function (Blueprint $table) {
            foreach (['import_max_rows_per_upload', 'import_max_uploads_per_month', 'import_multi_brand_allowed', 'import_reimport_allowed'] as $column) {
                if (Schema::connection('central')->hasColumn('tenant_limit_overrides', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::connection('central')->table('tenant_usage_snapshots', function (Blueprint $table) {
            if (Schema::connection('central')->hasColumn('tenant_usage_snapshots', 'imports_this_month')) {
                $table->dropColumn('imports_this_month');
            }
        });
    }

    private function seedPlanDefaults(): void
    {
        $rows = DB::connection('central')->table('package_pricings')->get(['id', 'slug']);

        foreach ($rows as $row) {
            $slug = strtolower((string) $row->slug);
            $tier = $this->tierFromSlug($slug);

            DB::connection('central')->table('package_pricings')->where('id', $row->id)->update(match ($tier) {
                'premium' => [
                    'import_max_rows_per_upload'    => -1,
                    'import_max_uploads_per_month'  => -1,
                    'import_multi_brand_allowed'    => true,
                    'import_reimport_allowed'       => true,
                ],
                'standard' => [
                    'import_max_rows_per_upload'    => 1000,
                    'import_max_uploads_per_month'  => 5,
                    'import_multi_brand_allowed'    => true,
                    'import_reimport_allowed'       => true,
                ],
                default => [
                    'import_max_rows_per_upload'    => 150,
                    'import_max_uploads_per_month'  => 1,
                    'import_multi_brand_allowed'    => false,
                    'import_reimport_allowed'       => false,
                ],
            });
        }
    }

    private function tierFromSlug(string $slug): string
    {
        foreach (['premium', 'agency', 'enterprise'] as $needle) {
            if (str_contains($slug, $needle)) {
                return 'premium';
            }
        }

        foreach (['standard', 'growth'] as $needle) {
            if (str_contains($slug, $needle)) {
                return 'standard';
            }
        }

        return 'basic';
    }
};
