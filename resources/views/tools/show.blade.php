@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/tools.css') }}">
@endpush

@section('title', $tool->translate('name') . ' · ' . __('messages.site_name'))
@section('meta_description', $tool->translate('description') ?: __('tools.tagline'))

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head">
        <h1>{{ $tool->translate('name') }}</h1>
        @if ($d = $tool->translate('description'))
            <p class="lead">{{ $d }}</p>
        @endif
    </header>

    @if ($intro = $tool->translate('intro'))
        <div class="tool-intro"><p>{{ $intro }}</p></div>
    @endif

    <section class="home-section">
        @if ($tool->hasView())
            @include($tool->viewName())
        @else
            <p class="notice">—</p>
        @endif
    </section>
@endsection
