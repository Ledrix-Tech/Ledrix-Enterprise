<?php

namespace Tests\Unit;

use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use App\Models\Central\TenantDataExportRequest;
use App\Services\Tenant\TenantBackupService;
use App\Services\Tenant\TenantDataExportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class TenantBackupServiceTest extends TestCase
{
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->bootSqlitePrimary();
    }

    public function test_backup_now_tags_purpose_and_audits(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('zip extension required');
        }

        $tenant = $this->makeTenant('backup-co');

        DB::connection('primary')->table('leads')->insert([
            'tenant_id' => $tenant->id,
            'name'      => 'Backup Lead',
            'email'     => 'lead@example.com',
            'password'  => 'should-not-export',
        ]);

        $export = app(TenantBackupService::class)->backupNow((int) $tenant->id, [
            'type' => 'super_admin',
            'id'   => 9,
            'name' => 'Ops',
        ]);

        $this->assertSame('ready', $export->status);
        $this->assertSame('backup', data_get($export->meta, 'purpose'));
        $this->assertTrue(Storage::disk('local')->exists($export->file_path));
        $this->assertTrue(
            AuditLog::query()
                ->where('action', 'tenant.backup_created')
                ->where('tenant_id', $tenant->id)
                ->where('subject_id', $export->id)
                ->exists()
        );

        Storage::disk('local')->delete($export->file_path);
    }

    public function test_dry_run_restore_counts_without_writing(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('zip extension required');
        }

        $tenant = $this->makeTenant('restore-dry');

        DB::connection('primary')->table('leads')->insert([
            [
                'id'        => 11,
                'tenant_id' => $tenant->id,
                'name'      => 'Ada',
                'email'     => 'ada@example.com',
                'password'  => 'secret-a',
            ],
            [
                'id'        => 12,
                'tenant_id' => $tenant->id,
                'name'      => 'Bob',
                'email'     => 'bob@example.com',
                'password'  => 'secret-b',
            ],
        ]);

        $export = TenantDataExportRequest::query()->create([
            'tenant_id'         => $tenant->id,
            'requested_by_type' => 'super_admin',
            'reason'            => 'fixture',
            'status'            => 'approved',
            'meta'              => ['purpose' => 'backup'],
        ]);

        app(TenantDataExportService::class)->generate($export);
        $export->refresh();

        DB::connection('primary')->table('leads')->where('tenant_id', $tenant->id)->delete();
        $this->assertSame(0, DB::connection('primary')->table('leads')->count());

        $result = app(TenantBackupService::class)->restoreFromExport($export->id, [
            'dry_run' => true,
        ]);

        $this->assertTrue($result['dry_run']);
        $this->assertFalse($result['written']);
        $this->assertSame(2, $result['total_rows']);
        $this->assertSame(2, $result['tables']['leads'] ?? 0);
        $this->assertSame(0, DB::connection('primary')->table('leads')->count());
        $this->assertFalse(
            AuditLog::query()->where('action', 'tenant.restore_completed')->exists()
        );

        Storage::disk('local')->delete($export->file_path);
    }

    public function test_force_restore_upserts_and_skips_password(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('zip extension required');
        }

        $tenant = $this->makeTenant('restore-force');

        DB::connection('primary')->table('leads')->insert([
            'id'        => 21,
            'tenant_id' => $tenant->id,
            'name'      => 'Original',
            'email'     => 'orig@example.com',
            'password'  => 'keep-me',
        ]);

        $export = TenantDataExportRequest::query()->create([
            'tenant_id'         => $tenant->id,
            'requested_by_type' => 'super_admin',
            'reason'            => 'fixture',
            'status'            => 'approved',
        ]);

        app(TenantDataExportService::class)->generate($export);
        $export->refresh();

        DB::connection('primary')->table('leads')->where('id', 21)->update([
            'name'  => 'Changed',
            'email' => 'changed@example.com',
        ]);

        $result = app(TenantBackupService::class)->restoreFromExport($export->id, [
            'force'      => true,
            'actor_type' => 'super_admin',
            'actor_id'   => 1,
            'actor_name' => 'Ops',
        ]);

        $this->assertFalse($result['dry_run']);
        $this->assertTrue($result['written']);
        $this->assertSame(1, $result['total_rows']);

        $row = DB::connection('primary')->table('leads')->where('id', 21)->first();
        $this->assertSame('Original', $row->name);
        $this->assertSame('orig@example.com', $row->email);
        $this->assertSame('keep-me', $row->password);
        $this->assertTrue(
            AuditLog::query()
                ->where('action', 'tenant.restore_completed')
                ->where('tenant_id', $tenant->id)
                ->exists()
        );

        Storage::disk('local')->delete($export->file_path);
    }

    public function test_restore_refuses_unready_export(): void
    {
        $tenant = $this->makeTenant('not-ready');

        $export = TenantDataExportRequest::query()->create([
            'tenant_id' => $tenant->id,
            'status'    => 'pending',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Export is not ready');

        app(TenantBackupService::class)->restoreFromExport($export->id, ['dry_run' => true]);
    }

    public function test_restore_refuses_wrong_tenant(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('zip extension required');
        }

        $tenant = $this->makeTenant('owner');
        $other = $this->makeTenant('other');

        $export = TenantDataExportRequest::query()->create([
            'tenant_id'         => $tenant->id,
            'requested_by_type' => 'super_admin',
            'status'            => 'approved',
        ]);

        app(TenantDataExportService::class)->generate($export);
        $export->refresh();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not belong');

        try {
            app(TenantBackupService::class)->restoreFromExport($export->id, [
                'dry_run'   => true,
                'tenant_id' => (int) $other->id,
            ]);
        } finally {
            Storage::disk('local')->delete($export->file_path);
        }
    }

    private function makeTenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'name'     => ucfirst($slug),
            'slug'     => $slug,
            'email'    => $slug.'@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);
    }

    private function bootSqlitePrimary(): void
    {
        config([
            'database.connections.primary' => [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        app('db')->purge('primary');
        app('db')->reconnect('primary');

        Schema::connection('primary')->create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
    }
}
