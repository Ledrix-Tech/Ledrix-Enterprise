<?php

namespace App\Http\Controllers\FrontViews;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sandbox\RegisterDemoAccountRequest;
use App\Models\Central\DemoAccount;
use App\Services\Sandbox\EnterDemoWorkspaceService;
use App\Services\Sandbox\RegisterDemoAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SandboxAuthController extends Controller
{
    public function __construct(
        private RegisterDemoAccountService $registerDemo,
        private EnterDemoWorkspaceService $enterDemo,
    ) {}

    public function show(): \Illuminate\View\View
    {
        return view('front.pages.sandbox.register');
    }

    public function loginForm(): \Illuminate\View\View
    {
        return view('front.pages.sandbox.login');
    }

    public function store(RegisterDemoAccountRequest $request)
    {
        $account = $this->registerDemo->register(
            $request->validated(),
            $request->ip()
        );

        Auth::guard('demo')->login($account);
        $request->session()->regenerate();

        $url = $this->enterDemo->enter($account, 'admin');

        return redirect()->to($url)->with(
            'success',
            'Sandbox ready. This is shared demo data — start a real trial when you want a private workspace.'
        );
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $account = DemoAccount::query()
            ->where('email', strtolower(trim((string) $credentials['email'])))
            ->first();

        if (! $account || ! Hash::check($credentials['password'], $account->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Those sandbox credentials do not match.']);
        }

        if (! $account->isActive()) {
            return back()->withErrors(['email' => 'This sandbox login is no longer active.']);
        }

        $account->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        Auth::guard('demo')->login($account);
        $request->session()->regenerate();

        $url = $this->enterDemo->enter($account, 'admin');

        return redirect()->to($url);
    }

    public function open(Request $request, string $role)
    {
        abort_unless(in_array($role, ['admin', 'seller', 'client'], true), 404);

        $account = Auth::guard('demo')->user()
            ?? DemoAccount::query()->find($request->session()->get('demo_account_id'));

        if (! $account instanceof DemoAccount || ! $account->isActive()) {
            return redirect()
                ->route('sandbox.login')
                ->with('error', 'Sign in to the sandbox first.');
        }

        $url = $this->enterDemo->enter($account, $role);

        return redirect()->to($url);
    }

    public function logout(Request $request)
    {
        $this->enterDemo->exit();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('sandbox.register')
            ->with('success', 'You left the sandbox.');
    }
}
