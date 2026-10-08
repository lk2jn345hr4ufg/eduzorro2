{{--
    One match line.
    $f        App\Models\Fixture (odd relation optional)
    $links    [football-data team id => slug] — clubs with a page on the site
    $showDate bool   show the date instead of just the time
    $showComp bool   show the competition code badge
    $outcome  ?string W/D/L badge from a team's point of view
--}}
@php
    $links    = $links ?? [];
    $played   = $f->isPlayed();
    $live     = $f->isLive();
    $odd      = $f->relationLoaded('odd') ? $f->odd : null;
    $sides    = [
        'home' => [$f->home_api_id, $f->home_name, $f->home_logo, $f->goals_home],
        'away' => [$f->away_api_id, $f->away_name, $f->away_logo, $f->goals_away],
    ];
    $winner = $played
        ? ($f->goals_home > $f->goals_away ? 'home' : ($f->goals_home < $f->goals_away ? 'away' : null))
        : null;
@endphp
<div @class(['match', 'is-live' => $live, 'is-played' => $played])>
    <div class="match-time">
        @if ($live)
            <span class="live-dot">{{ __('football.live') }}</span>
        @elseif ($f->kickoff_at)
            @if (! empty($showDate))
                <span class="match-date">{{ $f->kickoff_at->translatedFormat('d M') }}</span>
            @endif
            @if (! $played)
                <time datetime="{{ $f->kickoff_at->toIso8601String() }}">{{ $f->kickoff_at->format('H:i') }}</time>
            @endif
        @endif
        @if (! empty($showComp) && $f->league_code)
            <span class="comp-code">{{ $f->league_code }}</span>
        @endif
    </div>

    <div class="match-teams">
        @foreach ($sides as $side => [$id, $name, $logo, $goals])
            <div @class(['match-team', 'is-winner' => $winner === $side])>
                @if ($logo)
                    <img src="{{ $logo }}" alt="" width="20" height="20" loading="lazy">
                @else
                    <span class="logo-ph" aria-hidden="true"></span>
                @endif
                @if ($id && isset($links[(int) $id]))
                    <a href="{{ route('sport.team', [$currentLanguage, $links[(int) $id]]) }}">{{ $name }}</a>
                @else
                    <span>{{ $name }}</span>
                @endif
                <span class="match-goals">{{ ($played || $live) && $goals !== null ? $goals : '' }}</span>
            </div>
        @endforeach
    </div>

    @if (! empty($outcome))
        <span class="form-badge is-{{ strtolower($outcome) }}" title="{{ __('football.outcome_' . $outcome) }}">{{ __('football.' . ['W' => 'win', 'D' => 'draw', 'L' => 'loss'][$outcome]) }}</span>
    @elseif ($odd && ! $played)
        <div class="match-odds" title="{{ __('football.odds') }}{{ $odd->bookmaker ? ' · ' . $odd->bookmaker : '' }}">
            <span><small>1</small>{{ number_format($odd->home, 2) }}</span>
            <span><small>X</small>{{ $odd->draw ? number_format($odd->draw, 2) : '—' }}</span>
            <span><small>2</small>{{ number_format($odd->away, 2) }}</span>
        </div>
    @endif
</div>
