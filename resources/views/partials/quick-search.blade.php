{{-- Quick search with live suggestions. Requires $currentRegion + $currentLanguage (shared by middleware). --}}
<form class="quick-search" action="{{ route('search.results', [$currentRegion, $currentLanguage]) }}" method="get" autocomplete="off">
    <div class="quick-search-field">
        <input
            type="search"
            name="q"
            value="{{ $term ?? '' }}"
            placeholder="{{ __('messages.search_placeholder') }}"
            aria-label="{{ __('messages.search') }}"
            data-suggest-url="{{ route('search.suggest', [$currentRegion, $currentLanguage]) }}">
        <ul class="suggestions" role="listbox" hidden></ul>
    </div>
    <button type="submit">{{ __('messages.search') }}</button>
</form>

@once
    @push('scripts')
        <script src="{{ asset('js/search.js') }}" defer></script>
    @endpush
@endonce
