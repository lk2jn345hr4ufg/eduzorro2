{{-- $scorers: championship code => top-10 Scorer rows --}}
<div class="tabs-block" data-tabs>
    <div class="chip-row" hidden>
        @foreach ($scorers as $code => $rows)
            @php($c = $competitions->get($code))
            <button type="button" class="chip" data-tab="{{ $code }}">
                @if ($c?->flag)<span class="flag" aria-hidden="true">{{ $c->flag }}</span>@endif
                {{ $c ? $c->translate('name') : $code }}
            </button>
        @endforeach
    </div>

    @foreach ($scorers as $code => $rows)
        @php($c = $competitions->get($code))
        <div class="tab-panel" data-panel="{{ $code }}">
            <h3 class="tab-panel-head">{{ $c ? $c->translate('name') : $code }}</h3>
            <div class="table-wrap">
                <table class="data-table scorers-table">
                    <thead>
                        <tr>
                            <th class="c-num">#</th>
                            <th>{{ __('football.player') }}</th>
                            <th>{{ __('football.club') }}</th>
                            <th class="c-num">{{ __('football.goals') }}</th>
                            <th class="c-num c-wide">{{ __('football.assists') }}</th>
                            <th class="c-num c-wide">{{ __('football.penalties') }}</th>
                            <th class="c-num c-wide">{{ __('football.played') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $s)
                            @php($id = (int) $s->team_api_id)
                            <tr>
                                <td class="c-num">{{ $s->rank }}</td>
                                <td><strong>{{ $s->player_name }}</strong>@if ($s->nationality)<span class="muted nat"> · {{ $s->nationality }}</span>@endif</td>
                                <td class="club-cell">
                                    @if ($s->team_logo)<img src="{{ $s->team_logo }}" alt="" width="18" height="18" loading="lazy">@endif
                                    @if (isset($links[$id]))
                                        <a href="{{ route('sport.team', [$currentLanguage, $links[$id]]) }}">{{ $s->team_name }}</a>
                                    @else
                                        {{ $s->team_name }}
                                    @endif
                                </td>
                                <td class="c-num"><strong>{{ $s->goals }}</strong></td>
                                <td class="c-num c-wide">{{ $s->assists ?? '–' }}</td>
                                <td class="c-num c-wide">{{ $s->penalties ?? '–' }}</td>
                                <td class="c-num c-wide">{{ $s->played ?? '–' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
@include('football.partials.tabs-script')
