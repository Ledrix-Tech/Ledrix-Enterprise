<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Support\GettingStartedGuide;
use Illuminate\Http\Request;

class GettingStartedController extends Controller
{
    use ResolvesOrganizationTenant;

    public function show()
    {
        return $this->organizationView('getting-started', [
            'tenant' => $this->organizationTenant(),
            'steps'  => GettingStartedGuide::steps(),
            'intro'  => GettingStartedGuide::intro(),
        ]);
    }

    public function dismiss(Request $request)
    {
        if ($request->boolean('persist', true)) {
            GettingStartedGuide::dismiss($this->organizationTenant());
        }

        GettingStartedGuide::closeForSession($request);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }
}
