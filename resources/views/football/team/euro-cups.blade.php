@if ($cupGroups->isEmpty())
    <section class="card"><p class="empty">{{ __('football.no_cups') }}</p></section>
@else
    @foreach ($cupGroups as $code => $matches)
        @php($cup = $cupNames->get($code))
        <section class="card">
            <div class="card-head">
                <h2>
                    @if ($cup?->emblem_url)<img class="inline-logo" src="{{ $cup->emblem_url }}" alt="" width="22" height="22">@endif
                    {{ $cup ? $cup->translate('name') : ($matches->first()->league_name ?: $code) }}
                </h2>
                @if ($cup && $cup->is_active)
                    <a class="card-more" href="{{ route('competition.show', [$currentLanguage, $cup]) }}">{{ __('football.view_all') }} →</a>
                @endif
            </div>
            <div class="match-list">
                @foreach ($matches as $f)
                    @include('football.partials.match-row', ['f' => $f, 'showDate' => true])
                @endforeach
            </div>
        </section>
    @endforeach
@endif
