<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateScimBearer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('scim.enabled')) {
            return response()->json([
                'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                'status'  => '404',
                'detail'  => 'SCIM provisioning is disabled.',
            ], 404);
        }

        $expected = (string) config('scim.bearer_token');

        if ($expected === '') {
            return response()->json([
                'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                'status'  => '503',
                'detail'  => 'SCIM bearer token is not configured.',
            ], 503);
        }

        $auth = (string) $request->header('Authorization', '');

        if (! str_starts_with($auth, 'Bearer ') || ! hash_equals($expected, trim(substr($auth, 7)))) {
            return response()->json([
                'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                'status'  => '401',
                'detail'  => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}
