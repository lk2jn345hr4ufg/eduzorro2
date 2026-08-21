@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/tools.css') }}">
@endpush

@section('title', __('tools.tools') . ' · ' . __('messages.site_name'))
@section('meta_description', __('tools.tagline'))

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head">
        <h1>{{ __('tools.tools') }}</h1>
        <p class="lead">{{ __('tools.tagline') }}</p>
    </header>

    @forelse ($tools as $category => $items)
        <section class="home-section">
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
@endsection
