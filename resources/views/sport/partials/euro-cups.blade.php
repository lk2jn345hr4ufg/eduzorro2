@if (empty($euroFixtures))
    <p>{{ __('sport.no_euro') }}</p>
@else
    @php($grouped = collect($euroFixtures)->groupBy(fn ($fx) => data_get($fx, 'league.name')))
    @foreach ($grouped as $competition => $fixtures)
        <h2>{{ $competition }}</h2>
        <table class="data-table">
            <thead><tr>
                <th>{{ __('sport.date') }}</th><th></th>
            </tr></thead>
            <tbody>
            @foreach ($fixtures as $fx)
                <tr>
                    <td>{{ \Illuminate\Support\Str::of(data_get($fx, 'fixture.date'))->replace('T', ' ')->limit(16, '') }}</td>
                    <td>
                        {{ data_get($fx, 'teams.home.name') }}
                        <strong>{{ data_get($fx, 'goals.home') }}:{{ data_get($fx, 'goals.away') }}</strong>
                        {{ data_get($fx, 'teams.away.name') }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endforeach
@endif
