{{-- $leaderTables: championship code => top-3 Standing rows --}}
<div class="leaders-grid">
    @foreach ($leaderTables as $code => $rows)
        @php($c = $competitions->get($code))
        <div class="leader-card">
            <div class="leader-card-head">
                @if ($c?->emblem_url)<img src="{{ $c->emblem_url }}" alt="" width="22" height="22" loading="lazy">@endif
                <a href="{{ route('competition.tab', [$currentLanguage, $c, 'standings']) }}">{{ $c->translate('name') }}</a>
                <span class="flag" aria-hidden="true">{{ $c->flag }}</span>
            </div>
            <ol class="leader-list">
                @foreach ($rows as $row)
                    @php($id = (int) $row->team_api_id)
                    <li>
                        <span class="pos pos-{{ $row->rank }}">{{ $row->rank }}</span>
                        @if ($row->team_logo)<img src="{{ $row->team_logo }}" alt="" width="18" height="18" loading="lazy">@endif
                        <span class="leader-name">
                            @if (isset($links[$id]))
                                <a href="{{ route('sport.team', [$currentLanguage, $links[$id]]) }}">{{ $row->team_name }}</a>
                            @else
                                {{ $row->team_name }}
                            @endif
                        </span>
                        <span class="leader-form">
                            @foreach (array_slice(array_values(array_filter(preg_split('/[,\s]*/', (string) $row->form))), -5) as $r)
                                @if (in_array(strtoupper($r), ['W', 'D', 'L'], true))
                                    <span class="form-dot is-{{ strtolower($r) }}"></span>
                                @endif
                            @endforeach
                        </span>
                        <strong class="leader-pts">{{ $row->points }}</strong>
                    </li>
                @endforeach
            </ol>
        </div>
    @endforeach
</div>
