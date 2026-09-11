<ol @class(['gs-guide-steps', 'gs-guide-steps--compact' => $compact ?? false])>
    @foreach ($steps as $index => $step)
        <li>
            <span class="gs-guide-index" aria-hidden="true">{{ $index + 1 }}</span>
            <div class="gs-guide-step">
                <strong>{{ $step['title'] }}</strong>
                <span>{{ $step['body'] }}</span>
            </div>
        </li>
    @endforeach
</ol>
@once
<style>
    .gs-guide-dialog {
        max-width: 560px;
        margin: 1.25rem auto;
    }
    .gs-guide-modal {
        border: 0;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 24px 64px rgba(26, 22, 71, 0.28);
        background: #fff;
    }
    .gs-guide-hero {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.25rem 1.35rem 1.1rem;
        background:
            radial-gradient(120% 140% at 100% 0%, rgba(139, 82, 254, 0.35), transparent 55%),
            linear-gradient(135deg, #4438c9 0%, #6d3ef0 52%, #8b52fe 100%);
        color: #fff;
    }
    .gs-guide-kicker {
        display: inline-block;
        margin-bottom: 0.4rem;
        padding: 0.15rem 0.55rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.16);
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }
    .gs-guide-title {
        margin: 0 0 0.35rem;
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.25;
        color: #fff;
    }
    .gs-guide-intro {
        margin: 0;
        max-width: 28rem;
        font-size: 0.9rem;
        line-height: 1.45;
        color: rgba(255, 255, 255, 0.86);
    }
    .gs-guide-close-form {
        margin: 0;
        flex-shrink: 0;
    }
    .gs-guide-close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border: 0;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.14);
        color: #fff;
        font-size: 0.95rem;
        cursor: pointer;
    }
    .gs-guide-close:hover,
    .gs-guide-close:focus-visible {
        background: rgba(255, 255, 255, 0.24);
        outline: none;
    }
    .gs-guide-body {
        padding: 1rem 1.25rem 0.35rem;
        background: #f8f7ff;
    }
    .gs-guide-steps {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 0.55rem;
    }
    .gs-guide-steps li {
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
        margin: 0;
        padding: 0.75rem 0.85rem;
        border-radius: 14px;
        background: #fff;
        border: 1px solid #ece9fb;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .gs-guide-index {
        flex-shrink: 0;
        width: 28px;
        height: 28px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(135deg, #4438c9, #8b52fe);
    }
    .gs-guide-step strong {
        display: block;
        margin-bottom: 0.15rem;
        color: #0f172a;
        font-size: 0.92rem;
        line-height: 1.3;
    }
    .gs-guide-step span {
        display: block;
        color: #64748b;
        font-size: 0.82rem;
        line-height: 1.4;
    }
    .gs-guide-steps--compact .gs-guide-step span {
        font-size: 0.8rem;
    }
    .gs-guide-more {
        margin-top: 0.85rem;
        font-size: 0.82rem;
        color: #64748b;
    }
    .gs-guide-more a {
        font-weight: 600;
        color: #4438c9;
        text-decoration: none;
    }
    .gs-guide-more a:hover {
        color: #8b52fe;
        text-decoration: underline;
    }
    .gs-guide-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.6rem;
        padding: 0.9rem 1.25rem 1.15rem;
        background: #fff;
        border-top: 1px solid #eef0f6;
    }
    .gs-guide-footer form {
        margin: 0;
    }
    .gs-guide-btn {
        min-height: 40px;
        padding: 0.5rem 1rem;
        border-radius: 11px;
        font-size: 0.9rem;
        font-weight: 600;
        border: 0;
        cursor: pointer;
    }
    .gs-guide-btn-ghost {
        background: #f1f5f9;
        color: #334155;
    }
    .gs-guide-btn-ghost:hover {
        background: #e2e8f0;
    }
    .gs-guide-btn-primary {
        background: linear-gradient(135deg, #4438c9, #8b52fe);
        color: #fff;
        box-shadow: 0 6px 16px rgba(68, 56, 201, 0.28);
    }
    .gs-guide-btn-primary:hover {
        filter: brightness(1.05);
    }
    @media (max-width: 520px) {
        .gs-guide-dialog {
            margin: 0.6rem;
        }
        .gs-guide-footer {
            flex-direction: column-reverse;
        }
        .gs-guide-footer form,
        .gs-guide-btn {
            width: 100%;
        }
    }
</style>
@endonce
