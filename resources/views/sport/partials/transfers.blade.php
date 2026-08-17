@php
    // Flatten API rows -> one row per transfer, resolving direction against THIS team.
    // API-Football repeats the same move across windows, so dedupe on player+date+clubs.
    $teamApiId = (int) $team->api_id;
    $teamName  = $team->translate('name');

    $rows = collect($transfers)
        ->flatMap(function ($t) {
            $player = data_get($t, 'player.name');
            return collect(data_get($t, 'transfers', []))->map(fn ($x) => [
                'player'  => $player,
                'date'    => data_get($x, 'date'),
                'type'    => data_get($x, 'type'),
                'in_id'   => (int) data_get($x, 'teams.in.id'),
                'in'      => data_get($x, 'teams.in.name'),
                'in_logo' => data_get($x, 'teams.in.logo'),
                'out_id'  => (int) data_get($x, 'teams.out.id'),
                'out'     => data_get($x, 'teams.out.name'),
                'out_logo'=> data_get($x, 'teams.out.logo'),
            ]);
        })
        ->filter(fn ($r) => $r['player'] && $r['date'])
        ->unique(fn ($r) => $r['player'].'|'.$r['date'].'|'.$r['in_id'].'|'.$r['out_id'])
        ->map(function ($r) use ($teamApiId) {
            // Arrival when this team is the destination.
            $isIn = $teamApiId && $r['in_id'] === $teamApiId;
            $r['direction'] = $isIn ? 'in' : 'out';
            $r['club']      = $isIn ? $r['out'] : $r['in'];
            $r['club_logo'] = $isIn ? $r['out_logo'] : $r['in_logo'];
            return $r;
        })
        // Drop self-to-self noise the API sometimes returns.
        ->filter(fn ($r) => $r['club'] && $r['in_id'] !== $r['out_id'])
        ->sortByDesc('date')
        ->values();

    // Group into transfer seasons: Jul..Jun => "2025/26".
    $seasons = $rows->groupBy(function ($r) {
        $d = \Illuminate\Support\Carbon::parse($r['date']);
        $start = $d->month >= 7 ? $d->year : $d->year - 1;
        return $start.'/'.substr((string) ($start + 1), -2);
    });
@endphp

@if ($rows->isEmpty())
    <p>{{ __('sport.no_transfers') }}</p>
@else
    @foreach ($seasons as $season => $items)
        @php($ins  = $items->where('direction', 'in')->count())
        @php($outs = $items->where('direction', 'out')->count())

        <details class="transfer-season" @if ($loop->first) open @endif>
            <summary>
                <span class="transfer-season-title">{{ $season }}</span>
                <span class="transfer-season-counts">
                    <span class="t-badge t-in">↓ {{ $ins }}</span>
                    <span class="t-badge t-out">↑ {{ $outs }}</span>
                </span>
            </summary>

            <ul class="transfer-list">
                @foreach ($items as $r)
                    <li class="transfer-row is-{{ $r['direction'] }}">
                        <span class="t-badge t-{{ $r['direction'] }}">
                            {{ $r['direction'] === 'in' ? '↓ ' . __('sport.in') : '↑ ' . __('sport.out') }}
                        </span>

                        <span class="transfer-player">{{ $r['player'] }}</span>

                        <span class="transfer-club">
                            @if ($r['club_logo'])
                                <img src="{{ $r['club_logo'] }}" alt="" width="18" height="18" loading="lazy">
                            @endif
                            {{ $r['club'] }}
                        </span>

                        <span class="transfer-meta">
                            @if ($r['type'] && strtolower($r['type']) !== 'n/a')
                                <span class="transfer-type">{{ $r['type'] }}</span>
                            @endif
                            <time datetime="{{ $r['date'] }}">
                                {{ \Illuminate\Support\Carbon::parse($r['date'])->translatedFormat('d M Y') }}
                            </time>
                        </span>
                    </li>
                @endforeach
            </ul>
        </details>
    @endforeach
@endif
