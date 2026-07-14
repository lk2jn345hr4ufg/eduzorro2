{{-- Expects $business, $region, $language (string code) --}}
<article class="company-card">
    <div class="company-card-head">
        <h3><a href="{{ route('business.show', [$region, $language, $business]) }}">{{ $business->name }}</a></h3>
    </div>
    @if ($business->address)
        <p class="company-card-address">{{ $business->address }}</p>
    @endif
    @if ($business->edrpou)
        <p class="company-card-address">EDRPOU: {{ $business->edrpou }}</p>
    @endif
    <div class="company-card-links">
        <a class="btn-link" href="{{ route('business.show', [$region, $language, $business]) }}">{{ __('messages.view_profile') }} →</a>
        @if ($business->website)
            <a class="btn-link" href="{{ $business->website }}" target="_blank" rel="nofollow noopener">{{ __('messages.website') }} ↗</a>
        @endif
    </div>
</article>
