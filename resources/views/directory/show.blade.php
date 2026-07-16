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
                        <li>{!! \App\Support\Sanitize::rich($listing->contacts_text) !!}</li>
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

        {{-- Leave a review (moderated: appears after admin approval) --}}
        <section class="company-section" id="review-form">
            <h2>{{ __('messages.write_review') }}</h2>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form class="review-form" method="post"
                  action="{{ route('directory.review.store', [$region, request()->route('language'), $vertical, $listing]) }}">
                @csrf

                {{-- Honeypot: hidden from humans; bots that fill it are dropped silently --}}
                <div class="hp-field" aria-hidden="true">
                    <label>Website <input type="text" name="website_url" tabindex="-1" autocomplete="off" value=""></label>
                </div>

                <div class="form-row">
                    <label>{{ __('messages.your_name') }}
                        <input type="text" name="author_name" value="{{ old('author_name') }}" required maxlength="255">
                    </label>
                    <label>{{ __('messages.your_email') }}
                        <input type="email" name="author_email" value="{{ old('author_email') }}" maxlength="255">
                    </label>
                </div>

                <label class="rating-field">{{ __('messages.rating') }}
                    <select name="rating" required>
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" @selected(old('rating') == $i)>{{ str_repeat('★', $i) }}</option>
                        @endfor
                    </select>
                </label>

                <label>{{ __('messages.review_body') }}
                    <textarea name="body" rows="5" required minlength="10" maxlength="5000">{{ old('body') }}</textarea>
                </label>

                <label>{{ __('messages.captcha_label') }} {{ $captchaQuestion }} = ?
                    <input type="text" name="captcha" inputmode="numeric" required autocomplete="off" style="max-width:120px;">
                </label>

                <button type="submit" class="btn btn-primary">{{ __('messages.submit_review') }}</button>
            </form>
        </section>
    </article>
@endsection
