<?php

namespace App\View\Composers;

use App\Support\SandboxWorkspace;
use Illuminate\View\View;

class DemoSandboxBannerComposer
{
    public function compose(View $view): void
    {
        $isDemo = session()->has('demo_account_id') || SandboxWorkspace::currentIsSandbox();

        $view->with('isDemoSandbox', $isDemo);
        $view->with('demoSandboxRole', session('demo_sandbox_role', 'admin'));
    }
}
