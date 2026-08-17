@php
    $renderRow = function ($fx) {
        $home = data_get($fx, 'teams.home.name');
        $away = data_get($fx, 'teams.away.name');
        $hg   = data_get($fx, 'goals.home');
        $ag   = data_get($fx, 'goals.away');
        $date = data_get($fx, 'fixture.date');
        $comp = data_get($fx, 'league.name');
        return compact('home', 'away', 'hg', 'ag', 'date', 'comp');
    };
@endphp

@if (empty($upcoming) && empty($results))
    <p>{{ __('sport.no_fixtures') }}</p>
@else
    @if (! empty($upcoming))
        <h2>{{ __('sport.upcoming') }}</h2>
        <table class="data-table">
            <thead><tr>
                <th>{{ __('sport.date') }}</th><th>{{ __('sport.competition') }}</th><th></th>
            </tr></thead>
            <tbody>
            @foreach ($upcoming as $fx) @php($r = $renderRow($fx))
                <tr>
                    <td>{{ \Illuminate\Support\Str::of($r['date'])->replace('T', ' ')->limit(16, '') }}</td>
                    <td>{{ $r['comp'] }}</td>
                    <td>{{ $r['home'] }} — {{ $r['away'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if (! empty($results))
        <h2>{{ __('sport.results') }}</h2>
        <table class="data-table">
            <thead><tr>
                <th>{{ __('sport.date') }}</th><th>{{ __('sport.competition') }}</th><th></th>
            </tr></thead>
            <tbody>
            @foreach ($results as $fx) @php($r = $renderRow($fx))
                <tr>
                    <td>{{ \Illuminate\Support\Str::of($r['date'])->replace('T', ' ')->limit(16, '') }}</td>
                    <td>{{ $r['comp'] }}</td>
                    <td>{{ $r['home'] }} <strong>{{ $r['hg'] }}:{{ $r['ag'] }}</strong> {{ $r['away'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endif
