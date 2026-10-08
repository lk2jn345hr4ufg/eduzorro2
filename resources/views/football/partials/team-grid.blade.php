{{-- $teams Collection of Team; $showComp bool --}}
@if ($teams->isEmpty())
    <p class="empty">{{ __('football.no_teams') }}</p>
@else
    <div class="team-grid">
        @foreach ($teams as $t)
            <a class="team-card" href="{{ route('sport.team', [$currentLanguage, $t]) }}">
                @if ($t->logo_url)
                    <img src="{{ $t->logo_url }}" alt="" width="40" height="40" loading="lazy">
                @else
                    <span class="logo-ph is-lg" aria-hidden="true"></span>
                @endif
                <span class="team-card-name">{{ $t->translate('name') }}</span>
                @if (! empty($showComp) && $t->competition)
                    <span class="team-card-sub">{{ $t->competition->flag }} {{ $t->competition->translate('name') }}</span>
                @endif
            </a>
        @endforeach
    </div>
@endif
