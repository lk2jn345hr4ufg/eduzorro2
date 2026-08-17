@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/sport.css') }}">
@endpush

@php($title = $news->translate('title'))

@section('title', $title . ' · ' . __('messages.site_name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($news->translate('excerpt') ?: $title), 160))

@section('content')
    @include('partials.breadcrumbs')

    <article class="news-article">
        <header class="page-head">
            <h1>{{ $title }}</h1>
            <p class="lead">
                @if ($team)
                    <a href="{{ route('sport.team', [$currentRegion, $currentLanguage, $team->country, $team]) }}">
                        {{ $team->translate('name') }}
                    </a>
                @endif
                @if ($news->published_at)
                    · <time datetime="{{ $news->published_at->toDateString() }}">{{ $news->published_at->translatedFormat('d M Y') }}</time>
                @endif
            </p>
        </header>

        @if ($news->image_url)
            <img class="news-article-image" src="{{ $news->image_url }}" alt="" loading="lazy">
        @endif

        @if ($body = $news->translate('body'))
            <div class="news-article-body">
                @foreach (preg_split('/\n+/', $body) as $para)
                    @if (trim($para) !== '')
                        <p>{{ $para }}</p>
                    @endif
                @endforeach
            </div>
        @elseif ($ex = $news->translate('excerpt'))
            <div class="news-article-body"><p>{{ $ex }}</p></div>
        @endif

        @if ($news->source_url)
            <p class="news-source">
                {{ __('sport.source') }}:
                <a href="{{ $news->source_url }}" rel="nofollow noopener" target="_blank">{{ parse_url($news->source_url, PHP_URL_HOST) }}</a>
            </p>
        @endif

        <p><a href="{{ route('sport.news.index', [$currentRegion, $currentLanguage]) }}">← {{ __('sport.back_to_news') }}</a></p>
    </article>

    @if ($related->isNotEmpty())
        <section class="home-section">
            <h2>{{ __('sport.more_from_team') }}</h2>
            <ul class="link-list">
                @foreach ($related as $item)
                    <li>
                        <a href="{{ route('sport.news.show', [$currentRegion, $currentLanguage, $item]) }}">
                            {{ $item->translate('title') }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
