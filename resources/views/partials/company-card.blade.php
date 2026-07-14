{{-- Expects $company (with average_rating / reviews_count from withRatingSummary). --}}
<article class="company-card">
    <div class="company-card-head">
        <h3>
            <a href="{{ route('company.show', [$currentRegion, $currentLanguage, $company]) }}">
                {{ $company->name }}
            </a>
        </h3>
        @if ($company->is_verified)
            <span class="badge badge-verified">{{ __('messages.verified') }}</span>
        @endif
        <span class="badge badge-type">{{ $company->type === 'digital' ? __('messages.digital') : __('messages.local') }}</span>
    </div>

    @include('partials.stars', [
        'rating' => $company->average_rating ?? 0,
        'count'  => $company->reviews_count ?? 0,
    ])

    @if ($desc = $company->translate('description'))
        <p class="company-card-desc">{{ \Illuminate\Support\Str::limit(strip_tags($desc), 140) }}</p>
    @endif

    @if ($company->address)
        <p class="company-card-address">{{ $company->address }}</p>
    @endif

    <a class="btn-link" href="{{ route('company.show', [$currentRegion, $currentLanguage, $company]) }}">
        {{ __('messages.view_profile') }} →
    </a>
</article>
