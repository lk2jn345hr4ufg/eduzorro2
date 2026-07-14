<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $currentLanguage->direction ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', __('messages.site_name'))</title>
    <meta name="description" content="@yield('meta_description', __('messages.tagline'))">

    <link rel="canonical" href="{{ url()->current() }}">

    {{-- hreflang alternates for the current page --}}
    @foreach (\App\Support\Seo::hreflangAlternates() as $alt)
        <link rel="alternate" hreflang="{{ $alt['hreflang'] }}" href="{{ $alt['href'] }}">
    @endforeach

    {{-- Open Graph --}}
    <meta property="og:site_name" content="{{ __('messages.site_name') }}">
    <meta property="og:title" content="@yield('title', __('messages.site_name'))">
    <meta property="og:description" content="@yield('meta_description', __('messages.tagline'))">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,560;9..144,650&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500;600&display=swap">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('head')
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="{{ isset($currentRegion) ? route('region.home', [$currentRegion, $currentLanguage]) : route('home') }}">
            {{ __('messages.site_name') }}
        </a>

        @isset($currentRegion)
            <span class="coordinate-stamp" aria-hidden="true">
                <span class="coordinate-stamp-code">{{ $currentRegion->code }}</span>
                <span class="coordinate-stamp-sep">&middot;</span>
                <span class="coordinate-stamp-code">{{ strtoupper($currentLanguage->code) }}</span>
            </span>

            <nav class="switchers">
                {{-- Language switcher: same page, other language --}}
                <label class="switcher">
                    <span class="sr-only">{{ __('messages.choose_language') }}</span>
                    <select onchange="if(this.value)window.location=this.value">
                        @foreach (\App\Support\Seo::hreflangAlternates() as $alt)
                            <option value="{{ $alt['href'] }}" @selected($alt['hreflang'] === app()->getLocale())>
                                {{ strtoupper($alt['hreflang']) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                {{-- Region switcher: jumps to the chosen region home. Falls back
                     to that region's own first supported language if it
                     doesn't offer the one currently being viewed (e.g.
                     switching from Ukraine/Ukrainian to Russian-only Kazakhstan). --}}
                <label class="switcher">
                    <span class="sr-only">{{ __('messages.choose_region') }}</span>
                    <select onchange="if(this.value)window.location=this.value">
                        @foreach ($activeRegions as $region)
                            @php($regionLanguage = $region->languages->contains('id', $currentLanguage->id) ? $currentLanguage : $region->languages->first())
                            @continue(! $regionLanguage)
                            <option value="{{ route('region.home', [$region, $regionLanguage]) }}"
                                @selected($region->id === $currentRegion->id)>
                                {{ $region->translate('name') }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </nav>
        @endisset
    </div>
</header>

<main class="container">
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @yield('content')
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; {{ date('Y') }} {{ __('messages.site_name') }} — {{ __('messages.tagline') }}</p>
    </div>
</footer>

@stack('scripts')
</body>
</html>
