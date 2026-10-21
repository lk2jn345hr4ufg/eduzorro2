<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $currentLanguage->direction ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f1a14">

    <title>@yield('title', __('messages.site_name'))</title>
    <meta name="description" content="@yield('meta_description', __('messages.tagline'))">

    <link rel="canonical" href="{{ url()->current() }}">

    @php($alternates = \App\Support\Seo::hreflangAlternates())
    @foreach ($alternates as $alt)
        <link rel="alternate" hreflang="{{ $alt['hreflang'] }}" href="{{ $alt['href'] }}">
    @endforeach

    <meta property="og:site_name" content="{{ __('messages.site_name') }}">
    <meta property="og:title" content="@yield('title', __('messages.site_name'))">
    <meta property="og:description" content="@yield('meta_description', __('messages.tagline'))">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/logo-full.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Barlow+Condensed:wght@600;700&display=swap">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">

    <link rel="stylesheet" href="{{ \App\Support\Asset::version('css/football.css') }}">
    @stack('head')
</head>
<body>
@php($navLanguage = $currentLanguage ?? null)
{{-- Queried per request (one tiny query). Not cached: config/cache.php has
     serializable_classes = false, so cached Eloquent models come back broken. --}}
@php($navCompetitions = $navLanguage
    ? \App\Models\Competition::active()->ordered()->take(6)->get()
    : collect())

<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="{{ $navLanguage ? route('football.home', [$navLanguage]) : route('home') }}">
            <img class="brand-logo" src="{{ asset('images/logo.png') }}" alt="{{ __('messages.site_name') }}" width="40" height="40">
            <span class="brand-name">{{ __('messages.site_name') }}</span>
        </a>

        @if ($navLanguage)
            <nav class="main-nav" aria-label="{{ __('football.championships') }}">
                <a href="{{ route('football.home', [$navLanguage]) }}" @class(['is-active' => request()->routeIs('football.home')])>{{ __('messages.home') }}</a>
                @foreach ($navCompetitions->take(6) as $nav)
                    <a href="{{ route('competition.show', [$navLanguage, $nav]) }}"
                       @class(['is-active' => request()->route('competition')?->is($nav)])>
                        @if ($nav->flag)<span class="flag" aria-hidden="true">{{ $nav->flag }}</span>@endif
                        {{ $nav->translate('name') }}
                    </a>
                @endforeach
                @if (config('football.news_enabled'))
                    <a href="{{ route('sport.news.index', [$navLanguage]) }}" @class(['is-active' => request()->routeIs('sport.news.*')])>{{ __('football.news') }}</a>
                @endif
            </nav>

            @if (count($alternates) > 1)
                <label class="lang-switch">
                    <span class="sr-only">{{ __('messages.choose_language') }}</span>
                    <select onchange="if(this.value)window.location=this.value">
                        @foreach ($alternates as $alt)
                            <option value="{{ $alt['href'] }}" @selected($alt['hreflang'] === app()->getLocale())>{{ strtoupper($alt['hreflang']) }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
        @endif
    </div>
</header>

<main class="container page">
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @yield('content')
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <p>&copy; {{ date('Y') }} {{ __('messages.site_name') }} — {{ __('messages.tagline') }}</p>
        <p class="footer-note">{{ __('football.odds_note') }}</p>
    </div>
</footer>

{{-- Adds the visitor's own time under every kick-off time (match zone is
     rendered server-side). Uses the browser's time zone; nothing is stored. --}}
<script>
(function () {
    var nodes = document.querySelectorAll('time[data-kickoff]');
    if (!nodes.length || !window.Intl) return;
    var lang = document.documentElement.lang || 'en';
    var yours = @json(__('football.your_time'));
    var same = @json(__('football.same_as_yours'));
    var userZone = '';
    try { userZone = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) {}

    nodes.forEach(function (el) {
        var d = new Date(el.getAttribute('data-kickoff'));
        if (isNaN(d)) return;
        var out = el.nextElementSibling;
        if (!out || !out.classList.contains('user-time')) return;

        var userOffset = -d.getTimezoneOffset();            // minutes, at kick-off
        var matchOffset = parseInt(el.getAttribute('data-offset'), 10);
        var time, zone;
        try {
            time = d.toLocaleTimeString(lang, { hour: '2-digit', minute: '2-digit', hour12: false });
            var parts = new Intl.DateTimeFormat(lang, { timeZoneName: 'short' }).formatToParts(d);
            zone = (parts.find(function (p) { return p.type === 'timeZoneName'; }) || {}).value || '';
        } catch (e) { return; }

        // Day shift vs the match-local date (e.g. a late game in Brazil is
        // already tomorrow for a visitor in Europe).
        var t = d.getTime(), day = 864e5;
        var shift = Math.floor((t + userOffset * 6e4) / day) - Math.floor((t + matchOffset * 6e4) / day);
        var dayMark = shift > 0 ? ' +' + shift + @json(__('football.day_short')) : (shift < 0 ? ' ' + shift + @json(__('football.day_short')) : '');

        out.textContent = '';
        if (userOffset === matchOffset) {
            out.textContent = same;
        } else {
            var strong = document.createElement('b');
            strong.textContent = time + dayMark;
            var label = document.createElement('span');
            label.textContent = yours.replace(':zone', zone);
            out.appendChild(strong);
            out.appendChild(label);
        }
        out.title = userZone;
        out.hidden = false;
        el.title = el.title + (userZone ? ' · ' + userZone + ': ' + time : '');
    });
})();
</script>

@stack('scripts')
</body>
</html>
