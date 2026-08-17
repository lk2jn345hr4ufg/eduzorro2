@if (empty($standings))
    <p>{{ __('sport.no_standings') }}</p>
@else
    <table class="data-table standings">
        <thead><tr>
            <th>{{ __('sport.pos') }}</th>
            <th>{{ __('sport.club') }}</th>
            <th>{{ __('sport.played') }}</th>
            <th>{{ __('sport.won') }}</th>
            <th>{{ __('sport.drawn') }}</th>
            <th>{{ __('sport.lost') }}</th>
            <th>{{ __('sport.goals') }}</th>
            <th>{{ __('sport.points') }}</th>
        </tr></thead>
        <tbody>
        @foreach ($standings as $row)
            <tr class="{{ (int) data_get($row, 'team.id') === (int) $team->api_id ? 'is-current' : '' }}">
                <td>{{ data_get($row, 'rank') }}</td>
                <td>{{ data_get($row, 'team.name') }}</td>
                <td>{{ data_get($row, 'all.played') }}</td>
                <td>{{ data_get($row, 'all.win') }}</td>
                <td>{{ data_get($row, 'all.draw') }}</td>
                <td>{{ data_get($row, 'all.lose') }}</td>
                <td>{{ data_get($row, 'all.goals.for') }}:{{ data_get($row, 'all.goals.against') }}</td>
                <td><strong>{{ data_get($row, 'points') }}</strong></td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
