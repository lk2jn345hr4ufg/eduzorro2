{{--
    "Playing today" block.
    $todayGroups  competition code => today's Fixtures
    $nextDay      next date with matches (when nothing today)
    $competitions code => Competition; $links api id => team slug
--}}
@php
    $all      = $todayGroups->flatten();
    $liveN    = $all->filter(fn ($f) => $f->isLive())->count();
    $playedN  = $all->filter(fn ($f) => $f->isPlayed())->count();
    $leftN    = $all->count() - $liveN - $playedN;
    $hasOdds  = $all->contains(fn ($f) => $f->odd && ! $f->isPlayed());
@endphp

<div class="card-head">
    <h2>
        {{ __('football.playing_today') }}
        <span class="today-date">{{ now()->translatedFormat('l, j F') }}</span>
    </h2>
    @if ($all->isNotEmpty())
        <span class="today-counts">
            @if ($liveN)<span class="live-dot">{{ __('football.live') }} {{ $liveN }}</span>@endif
            <span>{{ __('football.today_left', ['count' => $leftN]) }}</span>
            @if ($playedN)<span class="muted">· {{ __('football.today_played', ['count' => $playedN]) }}</span>@endif
        </span>
    @endif
</div>

@if ($all->isEmpty())
    <div class="today-empty">
        <p>{{ __('football.no_matches_today') }}</p>
        @if ($nextDay)
            @php($d = \Illuminate\Support\Carbon::parse($nextDay))
            <a class="btn btn-ghost" href="#week">{{ __('football.next_match_day', ['date' => $d->translatedFormat('l, j F')]) }} →</a>
        @endif
    </div>
@else
    <div class="today-groups">
        @foreach ($todayGroups as $code => $matches)
            @php($c = $competitions->get($code))
            {{-- Live first, then upcoming by time, then finished. --}}
            @php($matches = $matches->sortBy(fn ($f) => [$f->isLive() ? 0 : ($f->isPlayed() ? 2 : 1), $f->kickoff_at->timestamp])->values())
            <div class="week-comp">
                <div class="week-comp-head">
                    @if ($c?->emblem_url)
                        <img src="{{ $c->emblem_url }}" alt="" width="20" height="20" loading="lazy">
                    @endif
                    @if ($c && $c->is_active)
                        <a href="{{ route('competition.tab', [$currentLanguage, $c, 'fixtures']) }}">{{ $c->translate('name') }}</a>
                    @else
                        <span>{{ $matches->first()->league_name ?: $code }}</span>
                    @endif
                    @if ($c?->flag)<span class="flag" aria-hidden="true">{{ $c->flag }}</span>@endif
                    <span class="week-comp-count">{{ $matches->count() }}</span>
                </div>
                <div class="match-list">
                    @foreach ($matches as $f)
                        @include('football.partials.match-row', ['f' => $f])
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    @if ($hasOdds)
        <p class="odds-note">{{ __('football.odds_note') }}</p>
    @endif
@endif
