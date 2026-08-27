<?php

namespace App\Support;

use Illuminate\Http\Request;

/** @deprecated Use TenantHostResolver — kept for backward compatibility. */
final class SellerLoginTenantResolver
{
    public static function resolve(Request $request): ?int
    {
        return TenantHostResolver::resolveTenantId($request);
    }
}
