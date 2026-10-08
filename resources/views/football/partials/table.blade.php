{{--
    Standings table(s).
    $tableGroups Collection group label => Standing rows
    $links       [api id => slug]
    $highlight   ?int team api id to highlight
    $compact     bool  fewer columns (dashboards)
--}}
@if ($tableGroups->isEmpty())
    <p class="empty">{{ __('football.no_data') }}</p>
@else
    @foreach ($tableGroups as $label => $rows)
        @if ($label !== '' && $tableGroups->count() > 1)
            <h3 class="table-group">{{ \Illuminate\Support\Str::startsWith(strtoupper($label), 'GROUP') ? __('football.group', ['name' => trim(substr($label, 5), " _")]) : $label }}</h3>
        @endif
        <div class="table-wrap">
            <table @class(['standings', 'is-compact' => ! empty($compact)])>
                <thead>
                    <tr>
                        <th class="c-pos">{{ __('football.pos') }}</th>
                        <th class="c-club">{{ __('football.club') }}</th>
                        <th>{{ __('football.p') }}</th>
                        @if (empty($compact))
                            <th class="c-wide">{{ __('football.win') }}</th>
                            <th class="c-wide">{{ __('football.draw') }}</th>
                            <th class="c-wide">{{ __('football.loss') }}</th>
                            <th class="c-wide">+/−</th>
                        @endif
                        <th>{{ __('football.gd') }}</th>
                        <th class="c-pts">{{ __('football.pts') }}</th>
                        @if (empty($compact))
                            <th class="c-form">{{ __('football.form') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php($id = (int) $row->team_api_id)
                        <tr @class(['is-highlight' => isset($highlight) && $highlight === $id])>
                            <td class="c-pos"><span class="pos pos-{{ $row->rank }}">{{ $row->rank }}</span></td>
                            <td class="c-club"><span class="club">
                                @if ($row->team_logo)
                                    <img src="{{ $row->team_logo }}" alt="" width="20" height="20" loading="lazy">
                                @endif
                                @if (isset($links[$id]))
                                    <a href="{{ route('sport.team', [$currentLanguage, $links[$id]]) }}">{{ $row->team_name }}</a>
                                @else
                                    <span>{{ $row->team_name }}</span>
                                @endif
                            </span></td>
                            <td>{{ $row->played }}</td>
                            @if (empty($compact))
                                <td class="c-wide">{{ $row->win }}</td>
                                <td class="c-wide">{{ $row->draw }}</td>
                                <td class="c-wide">{{ $row->lose }}</td>
                                <td class="c-wide">{{ $row->goals_for }}:{{ $row->goals_against }}</td>
                            @endif
                            @php($gd = (int) $row->goals_for - (int) $row->goals_against)
                            <td>{{ $gd > 0 ? '+' . $gd : $gd }}</td>
                            <td class="c-pts"><strong>{{ $row->points }}</strong></td>
                            @if (empty($compact))
                                <td class="c-form">
                                    @foreach (array_slice(array_filter(preg_split('/[,\s]*/', (string) $row->form)), -5) as $r)
                                        @php($r = strtoupper($r))
                                        @if (in_array($r, ['W', 'D', 'L'], true))
                                            <span class="form-dot is-{{ strtolower($r) }}" title="{{ __('football.outcome_' . $r) }}"></span>
                                        @endif
                                    @endforeach
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
@endif
