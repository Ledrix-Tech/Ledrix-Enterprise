@if (! empty($showGettingStartedModal) && ! empty($gettingStartedDismissUrl))
<div class="modal fade" id="gettingStartedModal" tabindex="-1" aria-labelledby="gettingStartedTitle" aria-hidden="true"
    data-bs-backdrop="true" data-bs-keyboard="true" data-gs-dismiss-url="{{ $gettingStartedDismissUrl }}"
    data-gs-csrf="{{ csrf_token() }}">
    <div class="modal-dialog modal-dialog-centered gs-guide-dialog">
        <div class="modal-content gs-guide-modal">
            <div class="gs-guide-hero">
                <div class="gs-guide-hero-copy">
                    <span class="gs-guide-kicker">Setup order</span>
                    <h2 class="gs-guide-title" id="gettingStartedTitle">Getting started</h2>
                    <p class="gs-guide-intro">{{ $gettingStartedIntro }}</p>
                </div>
                <form method="POST" action="{{ $gettingStartedDismissUrl }}" class="gs-guide-close-form">
                    @csrf
                    <input type="hidden" name="persist" value="0">
                    <button type="submit" class="gs-guide-close" aria-label="Close getting started guide" title="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
            <div class="gs-guide-body">
                @include('getting-started.steps', [
                    'steps' => $gettingStartedSteps,
                    'compact' => true,
                ])
                @if (! empty($gettingStartedHelpUrl))
                    <p class="gs-guide-more mb-0">
                        Need more detail on any section?
                        <a href="{{ $gettingStartedHelpUrl }}">Open in Help</a>
                    </p>
                @endif
            </div>
            <div class="gs-guide-footer">
                <form method="POST" action="{{ $gettingStartedDismissUrl }}">
                    @csrf
                    <input type="hidden" name="persist" value="0">
                    <button type="submit" class="gs-guide-btn gs-guide-btn-ghost" id="gettingStartedClose">Close</button>
                </form>
                <form method="POST" action="{{ $gettingStartedDismissUrl }}">
                    @csrf
                    <input type="hidden" name="persist" value="1">
                    <button type="submit" class="gs-guide-btn gs-guide-btn-primary" id="gettingStartedGotIt">
                        Got it, let’s go
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('gettingStartedModal');
        if (!el || typeof bootstrap === 'undefined') {
            return;
        }

        var persisted = false;
        el.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                if ((form.querySelector('[name="persist"]') || {}).value === '1') {
                    persisted = true;
                }
            });
        });

        el.addEventListener('hidden.bs.modal', function () {
            if (persisted) {
                return;
            }
            var url = el.getAttribute('data-gs-dismiss-url');
            var token = el.getAttribute('data-gs-csrf');
            if (!url || !token) {
                return;
            }
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: 'persist=0',
            });
        });

        bootstrap.Modal.getOrCreateInstance(el).show();
    });
</script>
@endif
