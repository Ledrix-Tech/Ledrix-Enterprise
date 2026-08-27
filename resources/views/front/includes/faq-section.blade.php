{{-- Shared FAQ accordion (marketing pages) --}}
@php
    $faqs = $faqs ?? config('seo.faq', []);
    $limit = $limit ?? null;
    if ($limit) {
        $faqs = array_slice($faqs, 0, (int) $limit);
    }
    $enterprise = $enterprise ?? false;
    $sectionId = $sectionId ?? 'faq';
    $accordionId = $accordionId ?? 'mktFaqAccordion';
    $title = $title ?? 'Frequently asked questions';
    $lead = $lead ?? 'Common questions about Ledrix CRM, trials, and multi-tenant sales workflows.';
    $sectionAlt = $sectionAlt ?? true;
    $colClass = $enterprise ? 'col-lg-10' : 'col-lg-12';
@endphp
@if (count($faqs) > 0)
    <section class="mkt-section {{ $sectionAlt ? 'mkt-section-alt' : '' }} {{ $enterprise ? 'mkt-faq-section-enterprise' : '' }}" id="{{ $sectionId }}" aria-labelledby="{{ $sectionId }}-heading">
        <div class="container">
            <div class="row justify-content-center">
                <div class="{{ $colClass }} w-100">
                    <div class="text-center mb-4">
                        <h2 class="mkt-section-title" id="{{ $sectionId }}-heading">{{ $title }}</h2>
                        <p class="mkt-section-lead">{{ $lead }}</p>
                    </div>
                    <div class="{{ $enterprise ? 'mkt-faq-enterprise-panel w-100' : 'w-100' }}">
                        <div class="accordion mkt-faq-accordion" id="{{ $accordionId }}">
                            @foreach ($faqs as $i => $faq)
                                <div class="accordion-item">
                                    <h3 class="accordion-header" id="{{ $accordionId }}-heading-{{ $i }}">
                                        <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#{{ $accordionId }}-collapse-{{ $i }}"
                                            aria-expanded="{{ $i === 0 ? 'true' : 'false' }}"
                                            aria-controls="{{ $accordionId }}-collapse-{{ $i }}">
                                            {{ $faq['question'] }}
                                        </button>
                                    </h3>
                                    <div id="{{ $accordionId }}-collapse-{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                                        aria-labelledby="{{ $accordionId }}-heading-{{ $i }}" data-bs-parent="#{{ $accordionId }}">
                                        <div class="accordion-body text-secondary">
                                            {{ $faq['answer'] }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @if ($limit && count(config('seo.faq', [])) > $limit)
                        <p class="text-center mt-4 mb-0">
                            <a href="{{ route('faq.get') }}" class="fw-semibold text-decoration-none">View all FAQs →</a>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif