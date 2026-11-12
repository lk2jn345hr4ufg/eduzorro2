{{-- $nationalGroups: code => (group label => Standing rows); $competitions code => Competition --}}
<div class="tabs-block" data-tabs>
    @if ($nationalGroups->count() > 1)
        <div class="chip-row" hidden>
            @foreach ($nationalGroups as $code => $groups)
                @php($c = $competitions->get($code))
                <button type="button" class="chip" data-tab="{{ $code }}">{{ $c ? $c->translate('name') : $code }}</button>
            @endforeach
        </div>
    @endif

    @foreach ($nationalGroups as $code => $groups)
        @php($c = $competitions->get($code))
        <div class="tab-panel" data-panel="{{ $code }}">
            <h3 class="tab-panel-head">
                @if ($c?->emblem_url)<img src="{{ $c->emblem_url }}" alt="" width="20" height="20">@endif
                @if ($c && $c->is_active)
                    <a href="{{ route('competition.tab', [$currentLanguage, $c, 'standings']) }}">{{ $c->translate('name') }}</a>
                @else
                    {{ $code }}
                @endif
            </h3>
            <div class="group-grid">
                @foreach ($groups as $label => $rows)
                    <div class="group-card">
                        @if ($label !== '')
                            <div class="group-card-head">{{ $label }}</div>
                        @endif
                        <table class="mini-table">
                            <thead>
                                <tr><th>#</th><th class="l">{{ __('football.club') }}</th><th>{{ __('football.p') }}</th><th>{{ __('football.gd') }}</th><th>{{ __('football.pts') }}</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    @php($gd = (int) $row->goals_for - (int) $row->goals_against)
                                    <tr>
                                        <td>{{ $row->rank }}</td>
                                        <td class="l">{{ $row->team_name }}</td>
                                        <td>{{ $row->played }}</td>
                                        <td>{{ $gd > 0 ? '+' . $gd : $gd }}</td>
                                        <td><strong>{{ $row->points }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
@include('football.partials.tabs-script')
