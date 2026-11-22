{{-- One championship / tournament card on the home page.
     $competition; $leaders code => Standing (rank 1); $today code => count --}}
@php($leader = $leaders->get($competition->code))
<a class="comp-card" href="{{ route('competition.show', [$currentLanguage, $competition]) }}">
    <span class="comp-card-top">
        @if ($competition->emblem_url)
            <img src="{{ $competition->emblem_url }}" alt="" width="36" height="36" loading="lazy">
        @else
            <span class="comp-emblem-ph" aria-hidden="true">{{ $competition->flag ?: '🏆' }}</span>
        @endif
        <span>
            <strong>{{ $competition->translate('name') }}</strong>
            <span class="comp-country">
                @if ($competition->isLeague())
                    {{ $competition->flag }} {{ $competition->country?->translate('name') }}
                @else
                    {{ $competition->flag }} {{ $competition->is_national ? __('football.national_teams') : __('football.club_tournament') }}
                @endif
            </span>
        </span>
    </span>
    <span class="comp-card-foot">
        @if ($leader)
            <span class="comp-leader" title="{{ __('football.leader') }}">🏆 {{ $leader->team_name }} · {{ $leader->points }}</span>
        @else
            <span class="comp-leader is-muted">—</span>
        @endif
        @if ($n = $today->get($competition->code))
            <span class="badge">{{ __('football.today_count', ['count' => $n]) }}</span>
        @endif
    </span>
</a>
