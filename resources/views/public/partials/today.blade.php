{{-- Секция «Матчи сегодня». Ожидает $todayMatches: матчи (LeagueMatch и Fixture), сгруппированные по названию турнира. --}}
<style>
    .today{margin:6px 0 28px}
    .today .th{display:flex;align-items:baseline;gap:14px;margin-bottom:14px}
    .today .th h2{font-family:var(--display);font-weight:400;text-transform:uppercase;font-size:22px;letter-spacing:1px}
    .today .th .date{font-size:12px;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:var(--muted)}
    .today .th .rule{flex:1;height:3px;background:var(--red)}
    .today .th a{font-size:11px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:var(--red)}
    .today .groups{display:grid;grid-template-columns:1fr;gap:18px;max-width:760px}
    .today .grp{background:var(--card);border:1px solid var(--line);border-radius:8px;overflow:hidden}
    .today .grp h3{font-size:11px;font-weight:800;letter-spacing:1.4px;text-transform:uppercase;color:var(--muted);padding:11px 16px;border-bottom:1px solid var(--line);background:#faf7f2}
    .today .row{display:grid;grid-template-columns:1fr auto 1fr auto;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--line)}
    .today .row:last-child{border-bottom:0}
    .today .row.lfc{background:rgba(200,16,46,.07);box-shadow:inset 3px 0 0 var(--red)}
    .today .tm{display:flex;align-items:center;gap:7px;font-size:14px;font-weight:700;min-width:0}
    .today .tm span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .today .tm.home{justify-content:flex-end;text-align:right}
    .today .tm img{width:18px;height:18px;object-fit:contain;flex:0 0 auto}
    .today .row.lfc .tm{color:var(--red)}
    .today .mid{min-width:52px;text-align:center}
    .today .sc{font-family:var(--display);font-size:18px;letter-spacing:1px;background:var(--wine);color:#fff;border-radius:3px;padding:1px 8px}
    .today .vs{color:#b9b0a6;font-weight:800}
    .today .st{min-width:120px;text-align:right}
    .today .st .ko{align-items:flex-end}
    .today .badge{font-size:10px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;padding:3px 8px;border-radius:99px;white-space:nowrap}
    .today .badge.live{background:var(--red);color:#fff}
    .today .badge.live::before{content:'';display:inline-block;width:6px;height:6px;border-radius:50%;background:#fff;margin-right:5px;vertical-align:1px;animation:todayPulse 1.2s infinite}
    .today .badge.ft{background:#eee;color:#555}
    .today .badge.pp{background:#fee2e2;color:#991b1b}
    @keyframes todayPulse{50%{opacity:.25}}
    @media(prefers-reduced-motion:reduce){.today .badge.live::before{animation:none}}
    @media(max-width:560px){
        .today .groups{grid-template-columns:1fr}
        .today .row{grid-template-columns:1fr auto 1fr;row-gap:6px}
        .today .st{grid-column:1/-1;text-align:center}
        .today .st .ko{align-items:center}
    }
</style>

<section class="today" id="today">
    <div class="th">
        <h2>Матчи сегодня</h2>
        <span class="date">{{ now(config('venues.today_timezone', 'Europe/London'))->isoFormat('dd, D MMMM') }}</span>
        <span class="rule"></span>
        <a href="{{ route('fixtures') }}">Расписание →</a>
    </div>

    @if($todayMatches->isEmpty())
        <div class="empty">
            Сегодня «Ливерпуль» не играет.
            @if(! empty($nextFixture))
                Следующий матч: <strong>{{ $nextFixture->home_team }} — {{ $nextFixture->away_team }}</strong>,
                {{ $nextFixture->venueKickoff()?->isoFormat('dd, D MMMM') }}.
            @endif
            <a href="{{ route('fixtures') }}" style="color:var(--red);font-weight:700">Расписание →</a>
        </div>
    @else
        <div class="groups">
            @foreach($todayMatches as $code => $matches)
                <div class="grp">
                    <h3>{{ $matches->first()->competitionName() }}</h3>
                    @foreach($matches as $m)
                        <div class="row {{ str_contains(mb_strtolower($m->home_team . ' ' . $m->away_team), 'liverpool') ? 'lfc' : '' }}">
                            <div class="tm home">
                                <span>{{ $m->home_team }}</span>
                                @if($m->home_crest ?? null)<img src="{{ $m->home_crest }}" alt="" loading="lazy">@endif
                            </div>
                            <div class="mid">
                                @if($m->hasResult() && $m->status !== 'scheduled')
                                    <span class="sc">{{ $m->home_score }}:{{ $m->away_score }}</span>
                                @else
                                    <span class="vs">—</span>
                                @endif
                            </div>
                            <div class="tm">
                                @if($m->away_crest ?? null)<img src="{{ $m->away_crest }}" alt="" loading="lazy">@endif
                                <span>{{ $m->away_team }}</span>
                            </div>
                            <div class="st">
                                @if($m->status === 'live')
                                    <span class="badge live">Live</span>
                                @elseif($m->status === 'finished')
                                    <span class="badge ft">Завершён</span>
                                @elseif($m->status === 'postponed')
                                    <span class="badge pp">Перенесён</span>
                                @else
                                    @include('public.partials.kickoff', ['f' => $m])
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif
</section>
