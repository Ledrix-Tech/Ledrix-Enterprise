@extends('central.layout.layout')

@section('title', 'Ledrix | Website Theme')

@section('central-content')
    @php
        $primary = old('primary_color', $settings['primary_color'] ?? '#4338ca');
        $secondary = old('secondary_color', $settings['secondary_color'] ?? '#8b52fe');
        $preview = $preview ?? [];
    @endphp

    <div class="sa-page-header">
        <div>
            <h1>Website theme</h1>
            <p>Change the front marketing site brand color (home, pricing, auth, landing pages). Like HubSpot brand color — Super Admin controls it here.</p>
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
            <div class="col-lg-6">
                <div class="sa-card">
                    <div class="sa-card-header"><h4>Brand colors</h4></div>
                    <div class="sa-card-body">
                        <form method="POST" action="{{ route('super-admin.theme.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label" for="primary_color">Primary (contrast) color</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" id="primary_color_picker" value="{{ $primary }}"
                                        class="form-control form-control-color" style="width: 3.5rem; height: 2.5rem;"
                                        oninput="document.getElementById('primary_color').value = this.value">
                                    <input type="text" id="primary_color" name="primary_color" value="{{ $primary }}"
                                        class="form-control font-monospace @error('primary_color') is-invalid @enderror"
                                        maxlength="7" pattern="^#?[0-9A-Fa-f]{6}$" required
                                        oninput="if(/^#[0-9A-Fa-f]{6}$/i.test(this.value)) document.getElementById('primary_color_picker').value = this.value">
                                </div>
                                <div class="form-text">Main buttons, links, and hero accents. Example: <code>#c93700</code></div>
                                @error('primary_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="secondary_color">Secondary color <span class="text-muted">(optional)</span></label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" id="secondary_color_picker" value="{{ $secondary }}"
                                        class="form-control form-control-color" style="width: 3.5rem; height: 2.5rem;"
                                        oninput="document.getElementById('secondary_color').value = this.value">
                                    <input type="text" id="secondary_color" name="secondary_color" value="{{ $secondary }}"
                                        class="form-control font-monospace @error('secondary_color') is-invalid @enderror"
                                        maxlength="7" pattern="^#?[0-9A-Fa-f]{6}$"
                                        oninput="if(/^#[0-9A-Fa-f]{6}$/i.test(this.value)) document.getElementById('secondary_color_picker').value = this.value">
                                </div>
                                <div class="form-text">Gradients / accents. Leave as default purple if unsure.</div>
                                @error('secondary_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-sa-primary">Save theme</button>
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="document.getElementById('primary_color').value='#c93700'; document.getElementById('primary_color_picker').value='#c93700';">
                                    Use #c93700
                                </button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('super-admin.theme.reset') }}" class="mt-3"
                            onsubmit="return confirm('Reset to default indigo (#4338ca)?');">
                            @csrf
                            <button type="submit" class="btn btn-link text-muted px-0">Reset to Ledrix default</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="sa-card">
                    <div class="sa-card-header"><h4>Live preview</h4></div>
                    <div class="sa-card-body">
                        <div class="rounded-3 p-4 mb-3 text-white"
                            style="background: linear-gradient(145deg, {{ $preview['primary_dark'] ?? '#312e81' }} 0%, {{ $primary }} 45%, {{ $preview['primary_light'] ?? '#6366f1' }} 100%);">
                            <div class="small opacity-75 mb-1">Hero gradient</div>
                            <div class="fw-bold fs-5">Ledrix front website</div>
                            <p class="mb-3 small opacity-90">Buttons and accents follow your primary color.</p>
                            <span class="btn btn-sm"
                                style="background: {{ $preview['on_primary'] ?? '#fff' }}; color: {{ $primary }}; font-weight: 600;">
                                Start free trial
                            </span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                            <span class="badge" style="background: {{ $primary }}; color: {{ $preview['on_primary'] ?? '#fff' }};">Primary</span>
                            <span class="badge" style="background: {{ $secondary }};">Secondary</span>
                            <span class="badge text-bg-light border">Dark {{ $preview['primary_dark'] ?? '' }}</span>
                            <span class="badge text-bg-light border">Light {{ $preview['primary_light'] ?? '' }}</span>
                        </div>
                        <p class="small text-muted mb-0">Open the public home page in another tab after saving (hard refresh) to confirm.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
