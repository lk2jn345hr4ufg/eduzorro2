@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/sport.css') }}">
@endpush

@section('title', $sport->translate('name') . ' · ' . __('messages.site_name'))

@section('content')
    @include('partials.breadcrumbs')
    <header class="page-head">
        <h1>{{ $sport->translate('name') }}</h1>
        @if ($desc = $sport->translate('description'))
            <p class="lead">{{ $desc }}</p>
        @endif
    </header>
@endsection
