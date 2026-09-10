@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/tools.css') }}">
@endpush

@php
    $totalTools = $tools->flatten()->count();
    $categories = $tools->keys();
@endphp

@section('title', __('tools.tools') . ' · ' . __('messages.site_name'))
@section('meta_description', __('tools.tagline'))

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head tools-hero">
        <h1>{{ __('tools.tools') }}</h1>
        <p class="lead">{{ __('tools.tagline') }}</p>

        @if ($totalTools)
            <p class="tools-count">{{ trans_choice('tools.count', $totalTools, ['count' => $totalTools]) }}</p>

            {{-- Anchor nav: with ~90 tools the page is long, so let people jump. --}}
            <ul class="tools-nav">
                @foreach ($categories as $category)
                    <li>
                        <a class="chip" href="#cat-{{ $category ?: 'other' }}">
                            {{ __('tools.category.' . ($category ?: 'other')) }}
                            <small>({{ $tools[$category]->count() }})</small>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </header>

    @forelse ($tools as $category => $items)
        <section class="home-section" id="cat-{{ $category ?: 'other' }}">
            <h2>{{ __('tools.category.' . ($category ?: 'other')) }}</h2>
            <div class="tool-grid">
                @foreach ($items as $tool)
                    <a class="tool-card" href="{{ route('tools.show', [$currentLanguage, $tool]) }}">
                        <h3>{{ $tool->translate('name') }}</h3>
                        @if ($d = $tool->translate('description'))
                            <p>{{ $d }}</p>
                        @endif
                        <span class="tool-card-cta">{{ __('tools.open') }} →</span>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <p>—</p>
    @endforelse

    @if ($totalTools)
        <p class="tools-footnote">{{ __('tools.footnote') }}</p>
    @endif
@endsection
