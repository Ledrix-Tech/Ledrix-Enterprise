<?php

namespace App\View\Composers;

use App\Support\GettingStartedGuide;
use Illuminate\View\View;

class GettingStartedComposer
{
    public function compose(View $view): void
    {
        $show = GettingStartedGuide::shouldShowModal();
        $adminPortal = (bool) auth('admin')->check();

        $view->with('showGettingStartedModal', $show);
        $view->with('gettingStartedSteps', GettingStartedGuide::steps());
        $view->with('gettingStartedIntro', GettingStartedGuide::intro());
        $view->with('gettingStartedDismissUrl', $adminPortal
            ? route('admin.org.getting-started.dismiss')
            : (auth('tenant')->check() ? route('tenant.getting-started.dismiss') : null));
        $view->with('gettingStartedHelpUrl', $adminPortal
            ? route('admin.org.getting-started')
            : (auth('tenant')->check() ? route('tenant.getting-started') : null));
    }
}
