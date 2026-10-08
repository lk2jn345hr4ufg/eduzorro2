{{-- $days: Collection date => Fixtures; $links; $empty message; $outcomes optional [fixture id => W/D/L] --}}
@if ($days->isEmpty())
    <p class="empty">{{ $empty ?? __('football.no_data') }}</p>
@else
    @php($todayKey = now()->toDateString())
    @php($tomorrowKey = now()->addDay()->toDateString())
    <div class="match-days">
        @foreach ($days as $date => $matches)
            @php($day = \Illuminate\Support\Carbon::parse($date))
            <section class="match-day">
                <h3 class="match-day-head">
                    @if ($date === $todayKey)
                        <span class="day-chip">{{ __('football.today') }}</span>
                    @elseif ($date === $tomorrowKey)
                        <span class="day-chip">{{ __('football.tomorrow') }}</span>
                    @endif
                    {{ $day->translatedFormat('l, j F Y') }}
                </h3>
                <div class="match-list">
                    @foreach ($matches as $f)
                        @include('football.partials.match-row', [
                            'f'        => $f,
                            'showComp' => $showComp ?? false,
                            'outcome'  => isset($outcomes) ? ($outcomes[$f->id] ?? null) : null,
                        ])
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
    @if ($days->flatten()->contains(fn ($f) => $f->relationLoaded('odd') && $f->odd))
        <p class="odds-note">{{ __('football.odds_note') }}</p>
    @endif
@endif
