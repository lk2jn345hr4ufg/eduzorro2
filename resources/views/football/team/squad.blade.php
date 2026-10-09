@if ($lines->isEmpty())
    <section class="card"><p class="empty">{{ __('football.no_squad') }}</p></section>
@else
    @if ($team->coach_name)
        <section class="card coach-card">
            <span class="stat-label">{{ __('football.coach') }}</span>
            <strong>{{ $team->coach_name }}</strong>
            @if ($team->coach_nationality)<span class="muted">· {{ $team->coach_nationality }}</span>@endif
        </section>
    @endif

    @foreach ($lines as $line => $players)
        <section class="card">
            <div class="card-head">
                <h2>{{ __('football.pos_' . $line) }}</h2>
                <span class="card-more is-muted">{{ $players->count() }}</span>
            </div>
            <div class="table-wrap">
                <table class="data-table squad-table">
                    <thead>
                        <tr>
                            <th class="c-num">{{ __('football.number') }}</th>
                            <th>{{ __('football.player') }}</th>
                            <th class="c-wide">{{ __('football.role') }}</th>
                            <th>{{ __('football.nationality') }}</th>
                            <th class="c-num">{{ __('football.age') }}</th>
                            <th class="c-wide">{{ __('football.contract') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($players as $p)
                            <tr>
                                <td class="c-num"><span class="shirt">{{ $p->shirt_number ?? '–' }}</span></td>
                                <td><strong>{{ $p->name }}</strong></td>
                                <td class="c-wide muted">{{ $p->position }}</td>
                                <td>{{ $p->nationality }}</td>
                                <td class="c-num" @if ($p->date_of_birth) title="{{ $p->date_of_birth->translatedFormat('d M Y') }}" @endif>{{ $p->age() ?? '–' }}</td>
                                <td class="c-wide muted">{{ $p->contract_until }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach

    @if ($team->squad_synced_at)
        <p class="odds-note">{{ __('football.squad_updated', ['date' => $team->squad_synced_at->translatedFormat('d M Y')]) }}</p>
    @endif
@endif
