@extends('layouts.app')

@php($regionName = $region->translate('name'))

@section('title', $listing->name . ' · ' . $regionName . ' · ' . __('messages.site_name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($listing->description ?? $listing->name), 155))

@section('content')
    @include('partials.breadcrumbs')

    <article class="company-profile">
        <header class="company-header">
            <div>
                <h1>{{ $listing->name }}</h1>
                <div class="company-meta">
                    @include('partials.stars', ['rating' => $listing->average_rating ?? 0, 'count' => $listing->reviews_count ?? 0])
                    @if ($listing->editorial_rating)
                        <span class="badge badge-type">{{ __('messages.rating') }}: {{ number_format($listing->editorial_rating, 1) }}/5</span>
                    @endif
                    <span class="badge badge-type">{{ $verticalLabel }}</span>
                </div>
                @if ($listing->specialization)
                    <p class="lead" style="margin-top:8px;">{{ $listing->specialization }}</p>
                @endif
            </div>
        </header>

        @if ($listing->description_title || $listing->description)
            <section class="company-section">
                <h2>{{ __('messages.about') }}</h2>
                @if ($listing->description_title)<h3>{!! \App\Support\Sanitize::inline($listing->description_title) !!}</h3>@endif
                <div class="prose">{!! \App\Support\Sanitize::rich($listing->description) !!}</div>
            </section>
        @endif

        @if ($listing->details_description)
            <section class="company-section">
                <div class="prose">{!! \App\Support\Sanitize::rich($listing->details_description) !!}</div>
            </section>
        @endif

        @if ($listing->prosAndCons->isNotEmpty())
            <section class="company-section">
                <div class="company-columns">
                    <div>
                        <h2>👍 Pros</h2>
                        <ul class="contact-list">
                            @foreach ($listing->prosAndCons->where('kind', 'pro') as $p)
                                <li>{{ $p->text }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <h2>👎 Cons</h2>
                        <ul class="contact-list">
                            @foreach ($listing->prosAndCons->where('kind', 'con') as $c)
                                <li>{{ $c->text }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </section>
        @endif

        @if ($listing->prices->isNotEmpty())
            <section class="company-section">
                <h2>{{ __('messages.pricing') }}</h2>
                <ul class="contact-list">
                    @foreach ($listing->prices as $price)
                        <li>
                            <strong>{{ $price->name }}</strong>
                            @if ($price->price)  — {{ $price->price }} @endif
                            @if ($price->lessons_count && $price->lessons_count !== '0') ({{ $price->lessons_count }} lessons) @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="company-columns">
            <section class="company-section">
                <h2>{{ __('messages.contacts') }}</h2>
                <ul class="contact-list">
                    @foreach ($listing->addresses as $addr)
                        <li><strong>{{ __('messages.address') }}:</strong> {{ $addr->address }}</li>
                    @endforeach
                    @if ($listing->contacts_text)
                        <li>{!! nl2br(e(strip_tags($listing->contacts_text, '<br><a>'))) !!}</li>
                    @endif
                    @if ($listing->website)
                        <li>
                            <a class="btn" href="{{ $listing->website }}" target="_blank" rel="nofollow noopener">
                                {{ __('messages.visit_website') }} ↗
                            </a>
                        </li>
                    @endif
                </ul>
            </section>

            @if ($listing->logo_url)
                <section class="company-section">
                    <img src="{{ $listing->logo_url }}" alt="{{ $listing->name }}" style="max-width:100%;border-radius:var(--radius-card);border:1px solid var(--line);">
                </section>
            @endif
        </div>

        @php($topicalCategories = $listing->taxonomyTerms->where('taxonomy', \App\Http\Controllers\DirectoryController::VERTICAL_TAXONOMY[$listing->vertical] ?? null))
        @if ($topicalCategories->isNotEmpty())
            <section class="company-section related">
                <h2>{{ __('messages.related_categories') }}</h2>
                <ul class="chip-list">
                    @foreach ($topicalCategories as $term)
                        <li>
                            <a class="chip" href="{{ route('directory.category', [$region, request()->route('language'), $vertical, $term->slug]) }}">
                                {{ $term->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Reviews (migrated from the old site, read-only) --}}
        <section class="company-section" id="reviews">
            <h2>{{ __('messages.reviews') }} ({{ $listing->reviews_count ?? 0 }})</h2>

            @forelse ($listing->approvedReviews as $review)
                <article class="review">
                    <div class="review-head">
                        @include('partials.stars', ['rating' => $review->rating])
                        <span class="review-author">{{ $review->author_name }}</span>
                        <time datetime="{{ $review->created_at?->toDateString() }}">{{ $review->created_at?->format('M j, Y') }}</time>
                    </div>
                    <p>{{ $review->body }}</p>
                </article>
            @empty
                <p class="empty">{{ __('messages.no_reviews') }}</p>
            @endforelse
        </section>
    </article>
@endsection
