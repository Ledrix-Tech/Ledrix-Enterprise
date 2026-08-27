<?php

namespace App\Services\Tenant;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Pragmatic tenant storage usage (MB) for plan limit max_storage_mb.
 */
class TenantStorageMeter
{
    /** @var list<string> */
    private const PATH_COLUMNS = [
        'attachment',
        'attachment_path',
        'file_path',
        'profile',
        'logo',
        'avatar',
        'image',
        'path',
    ];

    /** @var list<string> Whitelist for Schema-gated path-column fallback */
    private const FALLBACK_TABLES = [
        'client_tickets',
        'profile_details',
        'questionnairs',
        'tenants',
        'orders',
        'projects',
        'project_tasks',
        'leads',
        'clients',
        'admins',
        'sellers',
    ];

    /** @var list<array{table: string, column: string, disk: string|null, prefix: string|null}> */
    private const KNOWN_SOURCES = [
        ['table' => 'client_tickets', 'column' => 'attachment', 'disk' => null, 'prefix' => 'uploads/attachments'],
        ['table' => 'profile_details', 'column' => 'profile', 'disk' => null, 'prefix' => 'uploads/profiles'],
    ];

    public function usedMb(int $tenantId): int
    {
        try {
            $counted = [];
            $bytes = 0;

            $bytes += $this->bytesFromPublicDiskTenantPaths($tenantId, $counted);
            $bytes += $this->bytesFromBriefAttachments($tenantId, $counted);
            $bytes += $this->bytesFromKnownTables($tenantId, $counted);
            $bytes += $this->bytesFromFallbackPathColumns($tenantId, $counted);

            if ($bytes <= 0) {
                return 0;
            }

            return (int) max(0, (int) ceil($bytes / 1048576));
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * @param  array<string, true>  $counted
     */
    private function bytesFromPublicDiskTenantPaths(int $tenantId, array &$counted): int
    {
        $bytes = 0;

        try {
            $disk = Storage::disk('public');
            foreach ($disk->allFiles() as $path) {
                if (! $this->pathContainsTenantId($path, $tenantId)) {
                    continue;
                }
                $bytes += $this->sizeOfCountedPath($path, 'public', $counted);
            }
        } catch (Throwable) {
            // Disk missing or unreadable — skip.
        }

        return $bytes;
    }

    /**
     * Brief attachments live in questionnairs.meta.attachments (public disk relative paths).
     *
     * @param  array<string, true>  $counted
     */
    private function bytesFromBriefAttachments(int $tenantId, array &$counted): int
    {
        if (! Schema::hasTable('questionnairs') || ! Schema::hasColumn('questionnairs', 'meta')) {
            return 0;
        }

        if (! Schema::hasColumn('questionnairs', 'tenant_id')) {
            return 0;
        }

        $bytes = 0;

        try {
            $rows = DB::table('questionnairs')
                ->where('tenant_id', $tenantId)
                ->whereNotNull('meta')
                ->select(['meta'])
                ->cursor();

            foreach ($rows as $row) {
                $meta = is_string($row->meta) ? json_decode($row->meta, true) : $row->meta;
                if (! is_array($meta)) {
                    continue;
                }
                $attachments = $meta['attachments'] ?? [];
                if (! is_array($attachments)) {
                    continue;
                }
                foreach ($attachments as $relPath) {
                    if (! is_string($relPath) || $relPath === '') {
                        continue;
                    }
                    $bytes += $this->sizeOfCountedPath(ltrim($relPath, '/'), 'public', $counted);
                }
            }
        } catch (Throwable) {
            return 0;
        }

        return $bytes;
    }

    /**
     * @param  array<string, true>  $counted
     */
    private function bytesFromKnownTables(int $tenantId, array &$counted): int
    {
        $bytes = 0;

        foreach (self::KNOWN_SOURCES as $source) {
            $table = $source['table'];
            $column = $source['column'];

            if (! Schema::hasTable($table)
                || ! Schema::hasColumn($table, $column)
                || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            try {
                $rows = DB::table($table)
                    ->where('tenant_id', $tenantId)
                    ->whereNotNull($column)
                    ->where($column, '!=', '')
                    ->select([$column])
                    ->cursor();

                foreach ($rows as $row) {
                    $value = (string) $row->{$column};
                    $bytes += $this->resolveStoredFileBytes($value, $source['disk'], $source['prefix'], $counted);
                }
            } catch (Throwable) {
                continue;
            }
        }

        return $bytes;
    }

    /**
     * Fallback: known tables with tenant_id + a path column (Schema-gated).
     *
     * @param  array<string, true>  $counted
     */
    private function bytesFromFallbackPathColumns(int $tenantId, array &$counted): int
    {
        $bytes = 0;
        $skipTables = array_column(self::KNOWN_SOURCES, 'table');
        $skipTables[] = 'questionnairs';

        foreach (self::FALLBACK_TABLES as $table) {
            if (in_array($table, $skipTables, true)) {
                continue;
            }

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            foreach (self::PATH_COLUMNS as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                try {
                    $rows = DB::table($table)
                        ->where('tenant_id', $tenantId)
                        ->whereNotNull($column)
                        ->where($column, '!=', '')
                        ->select([$column])
                        ->cursor();

                    foreach ($rows as $row) {
                        $value = (string) $row->{$column};
                        $bytes += $this->resolveStoredFileBytes($value, 'public', null, $counted);
                    }
                } catch (Throwable) {
                    continue;
                }
            }
        }

        return $bytes;
    }

    /**
     * @param  array<string, true>  $counted
     */
    private function resolveStoredFileBytes(string $value, ?string $disk, ?string $prefix, array &$counted): int
    {
        $value = ltrim(str_replace('\\', '/', $value), '/');

        // Absolute or public_path style already on disk.
        if (is_file($value)) {
            return $this->sizeOfLocalFile($value, $counted);
        }

        $publicRoot = public_path($value);
        if (is_file($publicRoot)) {
            return $this->sizeOfLocalFile($publicRoot, $counted);
        }

        if ($prefix) {
            $prefixed = public_path(trim($prefix, '/').'/'.basename($value));
            if (is_file($prefixed)) {
                return $this->sizeOfLocalFile($prefixed, $counted);
            }
        }

        $relative = $value;
        if ($prefix && ! str_contains($value, '/')) {
            $relative = trim($prefix, '/').'/'.$value;
        }

        return $this->sizeOfCountedPath($relative, $disk ?? 'public', $counted);
    }

    /**
     * @param  array<string, true>  $counted
     */
    private function sizeOfCountedPath(string $path, string $disk, array &$counted): int
    {
        $key = $disk.':'.$path;
        if (isset($counted[$key])) {
            return 0;
        }
        $counted[$key] = true;

        try {
            if (Storage::disk($disk)->exists($path)) {
                return (int) Storage::disk($disk)->size($path);
            }

            $local = public_path($path);
            if (is_file($local)) {
                $size = @filesize($local);

                return $size === false ? 0 : (int) $size;
            }

            $storagePublic = storage_path('app/public/'.$path);
            if (is_file($storagePublic)) {
                $size = @filesize($storagePublic);

                return $size === false ? 0 : (int) $size;
            }

            return 0;
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * @param  array<string, true>  $counted
     */
    private function sizeOfLocalFile(string $absolutePath, array &$counted): int
    {
        $key = 'local:'.$absolutePath;
        if (isset($counted[$key])) {
            return 0;
        }
        $counted[$key] = true;

        $size = @filesize($absolutePath);

        return $size === false ? 0 : (int) $size;
    }

    private function pathContainsTenantId(string $path, int $tenantId): bool
    {
        $id = (string) $tenantId;
        $segments = preg_split('#[/\\\\]#', $path) ?: [];

        return in_array($id, $segments, true);
    }
}
