<?php

namespace App\Services\Tenant;

use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use App\Models\Central\TenantDataExportRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

class TenantBackupService
{
    public function __construct(
        private TenantDataExportService $exports
    ) {}

    /**
     * Create a workspace export ZIP tagged as a backup and generate it immediately.
     *
     * @param  array{type?: string, id?: int|null, name?: string|null}|null  $actor
     */
    public function backupNow(int $tenantId, ?array $actor = null): TenantDataExportRequest
    {
        $tenant = Tenant::query()->findOrFail($tenantId);

        $actorType = $actor['type'] ?? 'super_admin';
        $actorId = $actor['id'] ?? null;
        $actorName = $actor['name'] ?? null;

        $export = TenantDataExportRequest::query()->create([
            'tenant_id'         => $tenant->id,
            'requested_by_name' => $actorName,
            'requested_by_type' => $actorType,
            'reason'            => 'Ops backup',
            'status'            => 'approved',
            'reviewed_at'       => now(),
            'meta'              => ['purpose' => 'backup'],
        ]);

        $this->exports->generate($export);
        $export->refresh();

        $export->forceFill([
            'meta' => array_merge($export->meta ?? [], ['purpose' => 'backup']),
        ])->save();

        AuditLog::record(
            'tenant.backup_created',
            (int) $tenant->id,
            $actorType,
            $actorId,
            $actorName,
            [
                'subject_type' => 'tenant_data_export_request',
                'subject_id'   => $export->id,
                'description'  => 'Tenant CRM backup created',
                'after'        => [
                    'export_id' => $export->id,
                    'file_path' => $export->file_path,
                    'purpose'   => 'backup',
                ],
            ]
        );

        return $export->fresh();
    }

    /**
     * Restore CRM CSV tables from a ready export ZIP into the primary DB.
     *
     * Default is dry-run (counts only). Set force=true to write rows.
     *
     * @param  array{
     *     dry_run?: bool,
     *     force?: bool,
     *     tenant_id?: int|null,
     *     actor_type?: string,
     *     actor_id?: int|null,
     *     actor_name?: string|null
     * }  $options
     * @return array{dry_run: bool, export_id: int, tenant_id: int, tables: array<string, int>, total_rows: int, written: bool}
     */
    public function restoreFromExport(int $exportId, array $options = []): array
    {
        $force = (bool) ($options['force'] ?? false);
        $dryRun = $force ? false : (bool) ($options['dry_run'] ?? true);

        $export = TenantDataExportRequest::query()->findOrFail($exportId);

        if (! $export->isReady()) {
            throw new InvalidArgumentException('Export is not ready for restore.');
        }

        $expectedTenantId = isset($options['tenant_id']) ? (int) $options['tenant_id'] : null;
        if ($expectedTenantId !== null && $expectedTenantId !== (int) $export->tenant_id) {
            throw new InvalidArgumentException('Export does not belong to the expected tenant.');
        }

        $path = $export->absolutePath();
        if (! $path || ! is_file($path)) {
            throw new InvalidArgumentException('Export ZIP file is missing.');
        }

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive extension is required for restore.');
        }

        $tenantId = (int) $export->tenant_id;
        $counts = $this->applyCrmCsvFromZip($path, $tenantId, $dryRun);

        $result = [
            'dry_run'    => $dryRun,
            'export_id'  => $export->id,
            'tenant_id'  => $tenantId,
            'tables'     => $counts,
            'total_rows' => array_sum($counts),
            'written'    => ! $dryRun,
        ];

        if (! $dryRun) {
            AuditLog::record(
                'tenant.restore_completed',
                $tenantId,
                $options['actor_type'] ?? 'system',
                $options['actor_id'] ?? null,
                $options['actor_name'] ?? null,
                [
                    'subject_type' => 'tenant_data_export_request',
                    'subject_id'   => $export->id,
                    'description'  => 'Tenant CRM restore completed',
                    'after'        => $result,
                ]
            );
        }

        return $result;
    }

    /**
     * @return array<string, int>
     */
    private function applyCrmCsvFromZip(string $zipPath, int $tenantId, bool $dryRun): array
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Could not open export ZIP.');
        }

        $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ledrix-restore-'.uniqid();
        if (! mkdir($tmp, 0700, true) && ! is_dir($tmp)) {
            $zip->close();
            throw new RuntimeException('Could not create restore temp directory.');
        }

        try {
            $zip->extractTo($tmp);
            $zip->close();

            $counts = [];
            $crmDir = $tmp.DIRECTORY_SEPARATOR.'crm';

            foreach (TenantDataExportService::CRM_TABLES as $table) {
                $csvPath = $crmDir.DIRECTORY_SEPARATOR.$table.'.csv';
                if (! is_file($csvPath)) {
                    continue;
                }

                if (! Schema::connection('primary')->hasTable($table)) {
                    continue;
                }

                $counts[$table] = $this->restoreCsvTable($csvPath, $table, $tenantId, $dryRun);
            }

            return $counts;
        } finally {
            $this->deleteDirectory($tmp);
        }
    }

    private function restoreCsvTable(string $csvPath, string $table, int $tenantId, bool $dryRun): int
    {
        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Could not read {$csvPath}");
        }

        try {
            $header = fgetcsv($handle);
            if ($header === false || $header === [null] || $header === []) {
                return 0;
            }

            $header = array_map(static fn ($col) => (string) $col, $header);
            $schemaColumns = Schema::connection('primary')->getColumnListing($table);
            $schemaLookup = array_flip($schemaColumns);

            $count = 0;
            $rowsBuffer = [];

            while (($line = fgetcsv($handle)) !== false) {
                if ($line === [null] || $line === []) {
                    continue;
                }

                $assoc = [];
                foreach ($header as $i => $col) {
                    if ($col === '' || ! isset($schemaLookup[$col])) {
                        continue;
                    }
                    if (in_array($col, TenantDataExportService::STRIP_COLUMNS, true)) {
                        continue;
                    }
                    $assoc[$col] = $line[$i] ?? null;
                }

                if ($assoc === []) {
                    continue;
                }

                $assoc['tenant_id'] = $tenantId;
                $rowsBuffer[] = $assoc;
                $count++;

                if (! $dryRun && count($rowsBuffer) >= 100) {
                    $this->upsertRows($table, $rowsBuffer);
                    $rowsBuffer = [];
                }
            }

            if (! $dryRun && $rowsBuffer !== []) {
                $this->upsertRows($table, $rowsBuffer);
            }

            return $count;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function upsertRows(string $table, array $rows): void
    {
        $hasId = Schema::connection('primary')->hasColumn($table, 'id');

        DB::connection('primary')->transaction(function () use ($table, $rows, $hasId) {
            foreach ($rows as $row) {
                if ($hasId && isset($row['id']) && $row['id'] !== '' && $row['id'] !== null) {
                    $id = $row['id'];
                    unset($row['id']);
                    DB::connection('primary')->table($table)->updateOrInsert(
                        ['id' => $id],
                        $row
                    );
                } else {
                    unset($row['id']);
                    DB::connection('primary')->table($table)->insert($row);
                }
            }
        });
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        @rmdir($dir);
    }
}
