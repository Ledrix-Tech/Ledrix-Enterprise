@extends('central.layout.layout')

@section('title', 'Ledrix | SSO Settings')

@section('central-content')
    @php
        $enabled = (bool) old('enabled', $settings['enabled'] ?? false);
        $provider = old('provider_name', $settings['provider_name'] ?? 'OIDC');
        $issuer = old('issuer_url', $settings['issuer_url'] ?? '');
        $clientId = old('client_id', $settings['client_id'] ?? '');
        $redirectUri = old('redirect_uri', $settings['redirect_uri'] ?? '');
        $scopes = old('scopes', $settings['scopes'] ?? 'openid profile email');
        $audience = old('audience', $settings['audience'] ?? '');
    @endphp

    <div class="sa-page-header">
        <div>
            <h1>Single Sign-On (OIDC)</h1>
            <p>Configure an enterprise identity provider for Super Admin and CRM Admin login. SCIM provisioning syncs CRM admin users when <code>SCIM_ENABLED=true</code>.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($migrationRequired)
        <div class="alert alert-warning">
            <strong>Migration required.</strong>
            Run:
            <code>php artisan migrate --database=central --path=database/migrations/central --force</code>
        </div>
    @else
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="sa-card">
                    <div class="sa-card-header"><h4>OIDC provider</h4></div>
                    <div class="sa-card-body">
                        <form method="POST" action="{{ route('super-admin.sso-settings.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" role="switch" id="enabled"
                                    name="enabled" value="1" @checked($enabled)>
                                <label class="form-check-label" for="enabled">Enable SSO login buttons</label>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="provider_name">Provider name</label>
                                <input type="text" id="provider_name" name="provider_name" value="{{ $provider }}"
                                    class="form-control" maxlength="120" placeholder="Okta / Azure AD / Auth0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="issuer_url">Issuer URL</label>
                                <input type="url" id="issuer_url" name="issuer_url" value="{{ $issuer }}"
                                    class="form-control font-monospace @error('issuer_url') is-invalid @enderror"
                                    placeholder="https://idp.example.com/oauth2/default">
                                <div class="form-text">Discovery: <code>{issuer}/.well-known/openid-configuration</code></div>
                                @error('issuer_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="client_id">Client ID</label>
                                    <input type="text" id="client_id" name="client_id" value="{{ $clientId }}"
                                        class="form-control font-monospace @error('client_id') is-invalid @enderror">
                                    @error('client_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="client_secret">Client secret</label>
                                    <input type="password" id="client_secret" name="client_secret" value=""
                                        class="form-control font-monospace @error('client_secret') is-invalid @enderror"
                                        autocomplete="new-password"
                                        placeholder="{{ ($settings['has_client_secret'] ?? false) ? '•••••••• (leave blank to keep)' : 'Required to enable' }}">
                                    @error('client_secret')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="redirect_uri">Super Admin redirect URI <span class="text-muted">(optional override)</span></label>
                                <input type="url" id="redirect_uri" name="redirect_uri" value="{{ $redirectUri }}"
                                    class="form-control font-monospace" placeholder="{{ $defaultCallback }}">
                                <div class="form-text">
                                    Default SA callback: <code>{{ $defaultCallback }}</code><br>
                                    CRM Admin callback (fixed): <code>{{ $adminCallback }}</code>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="scopes">Scopes</label>
                                    <input type="text" id="scopes" name="scopes" value="{{ $scopes }}"
                                        class="form-control font-monospace" placeholder="openid profile email">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="audience">Audience <span class="text-muted">(optional)</span></label>
                                    <input type="text" id="audience" name="audience" value="{{ $audience }}"
                                        class="form-control font-monospace">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-sa-primary">Save SSO settings</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="sa-card">
                    <div class="sa-card-header"><h4>Notes</h4></div>
                    <div class="sa-card-body">
                        <ul class="small mb-0 ps-3">
                            <li class="mb-2">Register both callback URLs in your IdP.</li>
                            <li class="mb-2">Super Admin and CRM Admin must pre-exist with matching email.</li>
                            <li class="mb-2">Inactive Super Admins are refused.</li>
                            <li class="mb-2">SCIM: set <code>SCIM_ENABLED=true</code> and <code>SCIM_BEARER_TOKEN</code>; IdP syncs users at <code>/api/scim/v2/Users</code>.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
