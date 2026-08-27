<div class="container py-4" style="max-width: 760px;">
    @php
        $workspaceHost = $workspaceHost ?? \App\Support\TenantHostResolver::workspaceHostForSlug($tenant->slug);
        $workspaceBase = $workspaceBaseUrl ?? \App\Support\TenantHostResolver::workspaceBaseUrlForTenant($tenant);
        $panelUrls = $workspacePanelUrls ?? \App\Support\TenantHostResolver::workspacePanelUrlsForTenant($tenant);
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">Workspace URL &amp; branding</h4>
            <p class="text-muted mb-0 small">{{ $tenant->name }}</p>
        </div>
        <a href="{{ org_route('overview') }}" class="btn btn-outline-secondary btn-sm">Overview</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Default workspace URL — automatic from registration slug --}}
    <div class="card shadow-sm border-0 mb-4 border-start border-primary border-4">
        <div class="card-header bg-white">
            <span class="badge bg-primary me-2">Default</span> Your workspace URL
        </div>
        <div class="card-body">
            <p class="mb-3">
                Created automatically when you registered. Share these links with your team — <strong>no DNS setup required</strong>.
            </p>
            <div class="mb-3">
                <label class="form-label small text-muted mb-1">Workspace hostname</label>
                <div class="input-group">
                    <input type="text" class="form-control font-monospace" value="{{ $workspaceHost }}" readonly id="workspace-host">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText('{{ $workspaceHost }}')">Copy</button>
                </div>
            </div>
            <ul class="list-unstyled small mb-0">
                <li class="mb-2"><strong>Admin CRM:</strong> <a href="{{ $panelUrls['admin'] }}" target="_blank" rel="noopener">{{ $panelUrls['admin'] }}</a></li>
                <li class="mb-2"><strong>Seller panel:</strong> <a href="{{ $panelUrls['seller'] }}" target="_blank" rel="noopener">{{ $panelUrls['seller'] }}</a></li>
                <li class="mb-2"><strong>Client portal:</strong> <a href="{{ $panelUrls['client'] }}" target="_blank" rel="noopener">{{ $panelUrls['client'] }}</a></li>
                <li><strong>Organization billing:</strong> <a href="{{ $panelUrls['org'] }}" target="_blank" rel="noopener">{{ $panelUrls['org'] }}</a></li>
            </ul>
            <p class="form-text mt-3 mb-0">
                Slug <code>{{ $tenant->slug }}</code> comes from your organization name at signup. Contact support if you need a slug change.
            </p>
        </div>
    </div>

    @if ($hasCustomDomain)
        {{-- Bring-your-own domain — enterprise / advanced --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header">
                <span class="badge bg-secondary me-2">Advanced</span> Connect your own domain
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Optional — for agencies that want CRM on <em>their</em> domain (e.g. <code>crm.youragency.com</code>), not on <code>{{ $workspaceHost }}</code>.
                    You must add DNS records at your domain registrar (GoDaddy, Cloudflare, etc.).
                </p>

                <form method="POST" action="{{ org_route('domain.update') }}" class="mb-4">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label" for="custom_domain">Your domain hostname</label>
                        <input id="custom_domain" type="text" name="custom_domain"
                            class="form-control @error('custom_domain') is-invalid @enderror"
                            value="{{ old('custom_domain', $tenant->custom_domain) }}"
                            placeholder="crm.youragency.com">
                        @error('custom_domain')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">
                            Use a subdomain you control (not <code>{{ $workspaceHost }}</code> — that is already yours).
                            Leave blank and save to remove.
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save domain</button>
                </form>

                @if ($tenant->custom_domain)
                    <p class="mb-2">
                        Status:
                        @if ($tenant->custom_domain_verified)
                            <span class="badge bg-success">Verified</span>
                            — CRM also works on <code>{{ $tenant->custom_domain }}</code>
                        @else
                            <span class="badge bg-warning text-dark">Pending DNS</span>
                        @endif
                    </p>

                    <div class="border rounded p-3 bg-light mb-3">
                        <p class="small mb-2"><strong>Step-by-step at your DNS provider</strong></p>
                        <ol class="small mb-0">
                            <li class="mb-2">Log in where you bought <strong>{{ $tenant->custom_domain }}</strong> (or its parent domain).</li>
                            <li class="mb-2">
                                Add a <strong>CNAME</strong> record: host <code>{{ $tenant->custom_domain }}</code>
                                → points to <code>{{ $platformHost }}</code>
                                <br><em>Or</em> add <strong>TXT</strong> on <code>{{ $txtHost }}</code> = <code>{{ $verifyToken }}</code>
                            </li>
                            <li>Wait 5–30 minutes for DNS, then click Verify below.</li>
                        </ol>
                    </div>

                    @unless ($tenant->custom_domain_verified)
                        <form method="POST" action="{{ org_route('domain.verify') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary">Verify DNS</button>
                        </form>
                    @endunless
                @endif
            </div>
        </div>
    @endif

    @if ($hasWhiteLabel)
        <div class="card shadow-sm border-0">
            <div class="card-header">White-label logo</div>
            <div class="card-body">
                <p class="text-muted small">Replace Ledrix branding in the CRM chrome with your logo.</p>
                @if ($tenant->logo)
                    <div class="mb-3">
                        <img src="{{ asset('storage/'.$tenant->logo) }}" alt="Logo" style="max-height: 48px;">
                    </div>
                @endif
                <form method="POST" action="{{ org_route('domain.branding') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-end">
                    @csrf
                    <div>
                        <label class="form-label" for="logo">Logo image</label>
                        <input id="logo" type="file" name="logo" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
                @if ($tenant->logo)
                    <form method="POST" action="{{ org_route('domain.branding') }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="remove_logo" value="1">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove logo</button>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
