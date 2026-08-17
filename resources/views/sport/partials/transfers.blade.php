@php($rows = collect($transfers)->flatMap(fn ($t) => collect(data_get($t, 'transfers', []))->map(fn ($x) => [
        'player' => data_get($t, 'player.name'),
        'date'   => data_get($x, 'date'),
        'type'   => data_get($x, 'type'),
        'in'     => data_get($x, 'teams.in.name'),
        'out'    => data_get($x, 'teams.out.name'),
    ]))->sortByDesc('date')->values())
@if ($rows->isEmpty())
    <p>{{ __('sport.no_transfers') }}</p>
@else
    <table class="data-table">
        <thead><tr>
            <th>{{ __('sport.date') }}</th>
            <th>{{ __('sport.player') }}</th>
            <th>{{ __('sport.from_club') }}</th>
            <th>{{ __('sport.to_club') }}</th>
        </tr></thead>
        <tbody>
        @foreach ($rows as $r)
            <tr>
                <td>{{ $r['date'] }}</td>
                <td>{{ $r['player'] }}</td>
                <td>{{ $r['out'] }}</td>
                <td>{{ $r['in'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
