<?php

namespace App\Support;

use App\Models\Central\Tenant;
use Illuminate\Http\Request;

/**
 * Resolves tenant from HTTP host (verified custom domain or slug subdomain).
 * F-27: used globally via middleware and during panel login disambiguation.
 */
final class TenantHostResolver
{
    /** @var list<string> */
    private const RESERVED_SUBDOMAINS = ['www', 'app', 'api', 'admin', 'seller', 'client', 'mail'];

    public static function platformHost(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return strtolower((string) ($host ?: 'ledrix.co'));
    }

    public static function isPlatformHost(string $host): bool
    {
        $host = strtolower($host);

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.localhost')) {
            return true;
        }

        $platform = self::platformHost();

        return $host === $platform
            || str_ends_with($host, '.'.$platform);
    }

    public static function resolveTenant(Request $request): ?Tenant
    {
        if ($tenantId = session('tenant_id')) {
            return Tenant::on('central')->find((int) $tenantId);
        }

        $host = strtolower($request->getHost());

        if (self::isPlatformHost($host)) {
            return self::resolveFromSlugSubdomain($host);
        }

        $tenant = Tenant::on('central')
            ->where('custom_domain', $host)
            ->where('custom_domain_verified', true)
            ->first();

        return $tenant ?: self::resolveFromSlugSubdomain($host);
    }

    public static function resolveTenantId(Request $request): ?int
    {
        return self::resolveTenant($request)?->id;
    }

    public static function isCustomDomainHost(Request $request): bool
    {
        $host = strtolower($request->getHost());

        if (self::isPlatformHost($host)) {
            return false;
        }

        return Tenant::on('central')
            ->where('custom_domain', $host)
            ->where('custom_domain_verified', true)
            ->exists();
    }

    public static function isPlatformWorkspaceHost(string $host): bool
    {
        $host = strtolower(trim($host));
        $platform = self::displayPlatformHost();

        return $host === $platform || str_ends_with($host, '.'.$platform);
    }

    /**
     * Host used in {slug}.{platform} links. IPs are not valid parents, so local APP_URL
     * of 127.0.0.1 becomes localhost (browsers resolve *.localhost to this machine).
     */
    public static function displayPlatformHost(): string
    {
        $platform = self::platformHost();

        return filter_var($platform, FILTER_VALIDATE_IP) ? 'localhost' : $platform;
    }

    public static function workspaceHostForSlug(string $slug): string
    {
        return strtolower(trim($slug)).'.'.self::displayPlatformHost();
    }

    public static function workspaceBaseUrlForSlug(string $slug): string
    {
        $parsed = parse_url((string) config('app.url'));
        $scheme = $parsed['scheme'] ?? 'https';
        $port = $parsed['port'] ?? null;
        $url = $scheme.'://'.self::workspaceHostForSlug($slug);

        if ($port && ! in_array((int) $port, [80, 443], true)) {
            $url .= ':'.$port;
        }

        return $url;
    }

    public static function workspaceBaseUrlForTenant(Tenant $tenant): string
    {
        return self::workspaceBaseUrlForSlug((string) $tenant->slug);
    }

    /** @return array{admin: string, seller: string, client: string, org: string} */
    public static function workspacePanelUrlsForTenant(Tenant $tenant): array
    {
        $base = rtrim(self::workspaceBaseUrlForTenant($tenant), '/');

        return [
            'admin'  => $base.'/admin',
            'seller' => $base.'/seller',
            'client' => $base.'/client',
            'org'    => $base.'/tenant-profile',
        ];
    }

    private static function resolveFromSlugSubdomain(string $host): ?Tenant
    {
        $parts = explode('.', $host);
        $isLocalSlug = count($parts) === 2 && $parts[1] === 'localhost';

        if (count($parts) < 3 && ! $isLocalSlug) {
            return null;
        }

        $slug = $parts[0];

        if (in_array($slug, self::RESERVED_SUBDOMAINS, true)) {
            return null;
        }

        return Tenant::on('central')->where('slug', $slug)->first();
    }
}
