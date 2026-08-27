@php $i = $incident; @endphp
<div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="title" class="form-control" required maxlength="255"
        value="{{ old('title', $i->title ?? '') }}">
</div>
<div class="mb-3">
    <label class="form-label">Details</label>
    <textarea name="body" rows="4" class="form-control" maxlength="5000">{{ old('body', $i->body ?? '') }}</textarea>
</div>
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Severity</label>
        <select name="severity" class="form-select" required>
            @foreach (['minor', 'major', 'critical'] as $sev)
                <option value="{{ $sev }}" @selected(old('severity', $i->severity ?? 'minor') === $sev)>{{ ucfirst($sev) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
            @foreach (['investigating', 'identified', 'monitoring', 'resolved'] as $st)
                <option value="{{ $st }}" @selected(old('status', $i->status ?? 'investigating') === $st)>{{ ucfirst($st) }}</option>
            @endforeach
        </select>
    </div>
    @unless ($i)
        <div class="col-md-4 mb-3">
            <label class="form-label">Started at</label>
            <input type="datetime-local" name="started_at" class="form-control"
                value="{{ old('started_at') }}">
        </div>
    @endunless
</div>
