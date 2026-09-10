<?php

namespace App\Http\Middleware;

use App\Support\SandboxDatabase;
use App\Support\SandboxWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Run sandbox CRM queries on ledrix_demos (demos_db), not primary.
 */
class SwitchDemoSandboxDatabase
{
    public function handle(Request $request, Closure $next): Response
    {
        $useDemosDb = $request->session()->has('demo_account_id')
            || SandboxWorkspace::currentIsSandbox();

        if ($useDemosDb) {
            SandboxDatabase::activate();
        }

        try {
            return $next($request);
        } finally {
            if ($useDemosDb) {
                SandboxDatabase::deactivate();
            }
        }
    }
}
