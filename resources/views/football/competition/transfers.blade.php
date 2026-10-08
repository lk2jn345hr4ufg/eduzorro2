<section class="card">
    @if ($transfers->isEmpty())
        <p class="empty">{{ __('football.no_transfers') }}</p>
    @else
        <div class="table-wrap">
            <table class="data-table transfers-table">
                <thead>
                    <tr>
                        <th>{{ __('football.date') }}</th>
                        <th>{{ __('football.player') }}</th>
                        <th>{{ __('football.from') }}</th>
                        <th>{{ __('football.to') }}</th>
                        <th class="c-wide">{{ __('football.type') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transfers->unique(fn ($t) => $t->player_name . '|' . optional($t->transfer_date)->toDateString() . '|' . $t->in_api_id . '|' . $t->out_api_id) as $t)
                        @php($in = $transferTeams->get((int) $t->in_api_id))
                        @php($out = $transferTeams->get((int) $t->out_api_id))
                        <tr>
                            <td class="nowrap">{{ optional($t->transfer_date)->translatedFormat('d M Y') }}</td>
                            <td><strong>{{ $t->player_name }}</strong></td>
                            <td class="club-cell">
                                @if ($t->out_logo)<img src="{{ $t->out_logo }}" alt="" width="18" height="18" loading="lazy">@endif
                                @if ($out)
                                    <a href="{{ route('sport.team', [$currentLanguage, $out]) }}">{{ $t->out_name }}</a>
                                @else
                                    {{ $t->out_name }}
                                @endif
                            </td>
                            <td class="club-cell">
                                @if ($t->in_logo)<img src="{{ $t->in_logo }}" alt="" width="18" height="18" loading="lazy">@endif
                                @if ($in)
                                    <a href="{{ route('sport.team', [$currentLanguage, $in]) }}">{{ $t->in_name }}</a>
                                @else
                                    {{ $t->in_name }}
                                @endif
                            </td>
                            <td class="c-wide muted">{{ $t->type && strtolower($t->type) !== 'n/a' ? $t->type : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
