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
    $local    = \App\Support\MatchTime::local($f);
    $zone     = $local ? $local->getTimezone()->getName() : 'UTC';
    $venue    = \App\Support\MatchVenue::label($f);
    $winner = $played
        ? ($f->goals_home > $f->goals_away ? 'home' : ($f->goals_home < $f->goals_away ? 'away' : null))
        : null;
@endphp
<div @class(['match', 'is-live' => $live, 'is-played' => $played])>
    <div class="match-time">
        @if ($live)
            <span class="live-dot">{{ __('football.live') }}</span>
        @elseif ($local)
            @if (! empty($showDate))
                <span class="match-date">{{ $local->translatedFormat('d M') }}</span>
            @endif
            @if (! $played)
                {{-- Match-local time + zone; the visitor's time is added by JS (layout). --}}
                <time class="kickoff"
                      datetime="{{ $f->kickoff_at->toIso8601ZuluString() }}"
                      data-kickoff="{{ $f->kickoff_at->toIso8601ZuluString() }}"
                      data-offset="{{ $local->utcOffset() }}"
                      title="{{ __('football.match_time_title', ['time' => $local->format('H:i'), 'city' => \App\Support\MatchTime::city($zone), 'zone' => \App\Support\MatchTime::abbr($local)]) }}">
                    {{ $local->format('H:i') }} <small class="tz">{{ \App\Support\MatchTime::abbr($local) }}</small>
                </time>
                <span class="user-time" hidden></span>
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
        @if ($venue)
            <div class="match-venue" title="{{ __('football.venue') }}: {{ $venue }}">
                <svg viewBox="0 0 16 16" width="11" height="11" aria-hidden="true"><path fill="currentColor" d="M8 0a5.5 5.5 0 0 0-5.5 5.5C2.5 9.6 8 16 8 16s5.5-6.4 5.5-10.5A5.5 5.5 0 0 0 8 0Zm0 7.8a2.3 2.3 0 1 1 0-4.6 2.3 2.3 0 0 1 0 4.6Z"/></svg>
                <span>{{ $venue }}</span>
            </div>
        @endif
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
