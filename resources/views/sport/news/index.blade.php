@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/sport.css') }}">
@endpush

@section('title', __('sport.sports_news') . ' · ' . __('messages.site_name'))
@section('meta_description', __('sport.latest_news'))

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head">
        <h1>{{ __('sport.sports_news') }}</h1>
        <p class="lead">{{ __('sport.latest_news') }}</p>
    </header>

    <section class="home-section">
        @if ($news->isEmpty())
            <p>{{ __('sport.no_news') }}</p>
        @else
            <div class="news-list">
                @foreach ($news as $item)
                    <article class="news-item">
                        @if ($item->image_url)
                            <a href="{{ route('sport.news.show', [$currentRegion, $currentLanguage, $item]) }}">
                                <img src="{{ $item->image_url }}" alt="" loading="lazy">
                            </a>
                        @endif
                        <div>
                            <h3>
                                <a href="{{ route('sport.news.show', [$currentRegion, $currentLanguage, $item]) }}">
                                    {{ $item->translate('title') }}
                                </a>
                            </h3>
                            <p class="news-feed-meta">
                                @if ($item->team)
                                    <a href="{{ route('sport.team', [$currentRegion, $currentLanguage, $item->team->country, $item->team]) }}">
                                        {{ $item->team->translate('name') }}
                                    </a>
                                @endif
                                @if ($item->published_at)
                                    · <time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->translatedFormat('d M Y') }}</time>
                                @endif
                            </p>
                            @if ($ex = $item->translate('excerpt'))
                                <p>{{ \Illuminate\Support\Str::limit($ex, 160) }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($news->hasPages())
                <nav class="pager">
                    @if ($news->onFirstPage())
                        <span class="pager-btn is-disabled">←</span>
                    @else
                        <a class="pager-btn" href="{{ $news->previousPageUrl() }}" rel="prev">←</a>
                    @endif
                    <span class="pager-info">{{ $news->currentPage() }} / {{ $news->lastPage() }}</span>
                    @if ($news->hasMorePages())
                        <a class="pager-btn" href="{{ $news->nextPageUrl() }}" rel="next">→</a>
                    @else
                        <span class="pager-btn is-disabled">→</span>
                    @endif
                </nav>
            @endif
        @endif
    </section>
@endsection
