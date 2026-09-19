<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') · LiverpoolIn</title>
    <style>
        :root { --lfc:#c8102e; --dark:#1a1a1a; --line:#e6e6e8; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif; color:#1a1a1a; background:#f4f4f6; }
        .shell { display:flex; min-height:100vh; }
        aside { width:220px; background:var(--dark); color:#fff; padding:18px 0; flex-shrink:0; }
        aside .brand { font-weight:800; font-size:18px; padding:0 20px 16px; letter-spacing:.5px; }
        aside .brand small { display:block; color:#c8102e; font-size:11px; letter-spacing:2px; }
        aside a { display:block; color:#cfcfcf; text-decoration:none; padding:11px 20px; font-size:14px; font-weight:500; }
        aside a:hover { background:#2a2a2a; color:#fff; }
        aside a.active { background:var(--lfc); color:#fff; }
        aside .sep { border-top:1px solid #333; margin:12px 0; }
        aside .grp { font-size:10px; letter-spacing:1.5px; text-transform:uppercase; color:#6f6f6f; padding:8px 20px 4px; font-weight:800; }
        main { flex:1; padding:26px 32px; max-width:1000px; }
        h1 { margin:0 0 20px; font-size:22px; }
        .card { background:#fff; border:1px solid var(--line); border-radius:10px; padding:16px 18px; margin-bottom:16px; }
        .card h3 { margin:0 0 10px; font-size:15px; }
        .muted { color:#777; font-size:13px; }
        .grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
        .stat { background:#fff; border:1px solid var(--line); border-radius:10px; padding:16px; text-align:center; }
        .stat .n { font-size:28px; font-weight:800; }
        .stat .l { font-size:12px; text-transform:uppercase; color:#888; letter-spacing:.5px; }
        .badge { display:inline-block; padding:2px 8px; border-radius:99px; font-size:11px; font-weight:700; text-transform:uppercase; }
        .badge.scraped{background:#eee;color:#666} .badge.editing{background:#fef9c3;color:#854d0e}
        .badge.edited{background:#dbeafe;color:#1e40af} .badge.published{background:#dcfce7;color:#166534}
        .badge.failed{background:#fee2e2;color:#991b1b} .badge.scheduled{background:#e0e7ff;color:#3730a3}
        .badge.finished{background:#dcfce7;color:#166534} .badge.postponed{background:#fee2e2;color:#991b1b}
        .btn { display:inline-block; padding:7px 12px; border-radius:6px; border:0; cursor:pointer; font-size:13px; text-decoration:none; }
        .btn.primary{background:var(--lfc);color:#fff} .btn.ghost{background:#eee;color:#333} .btn.danger{background:#fee2e2;color:#991b1b}
        .flash{padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px}
        .flash.ok{background:#dcfce7;color:#166534} .flash.err{background:#fee2e2;color:#991b1b}
        input,textarea,select{width:100%;padding:9px;border:1px solid #ccc;border-radius:6px;font:inherit}
        textarea{min-height:160px}
        label{font-size:13px;font-weight:600;display:block;margin-bottom:4px}
        table{width:100%;border-collapse:collapse} td,th{padding:9px;border-bottom:1px solid var(--line);text-align:left;font-size:14px}
        th{font-size:12px;text-transform:uppercase;color:#888;letter-spacing:.5px}
        code{background:#f4f4f6;padding:1px 5px;border-radius:3px;font-size:12.5px}
        form.inline{display:inline}
        .row{display:flex;gap:12px} .row>div{flex:1}
        .topbar{display:flex;align-items:center;gap:8px;margin-bottom:20px} .topbar h1{flex:1;margin:0}
    </style>
</head>
<body>
<div class="shell">
    <aside>
        <div class="brand">LiverpoolIn<small>ADMIN</small></div>
        @php $r = Route::currentRouteName(); @endphp

        <a href="{{ route('admin.dashboard') }}" class="{{ $r==='admin.dashboard'?'active':'' }}">Панель</a>

        <div class="grp">Контент</div>
        <a href="{{ route('admin.articles') }}" class="{{ str_starts_with($r,'admin.articles')?'active':'' }}">Новости</a>
        <a href="{{ route('admin.posts') }}" class="{{ str_starts_with($r,'admin.posts')?'active':'' }}">Статьи</a>
        <a href="{{ route('admin.categories') }}" class="{{ str_starts_with($r,'admin.categories')?'active':'' }}">Категории</a>

        <div class="grp">Данные</div>
        <a href="{{ route('admin.fixtures') }}" class="{{ str_starts_with($r,'admin.fixtures')?'active':'' }}">Матчи</a>
        <a href="{{ route('admin.apifootball') }}" class="{{ str_starts_with($r,'admin.apifootball')?'active':'' }}">API-Football</a>

        <div class="grp">Система</div>
        <a href="{{ route('admin.seo') }}" class="{{ str_starts_with($r,'admin.seo')?'active':'' }}">SEO / Мета-теги</a>
        <a href="{{ route('admin.settings') }}" class="{{ $r==='admin.settings'?'active':'' }}">Настройки</a>

        <div class="sep"></div>
        <a href="{{ route('home') }}" target="_blank">Открыть сайт ↗</a>
        @if(Route::has('logout'))
            <form method="post" action="{{ route('logout') }}">@csrf
                <button type="submit" style="all:unset;cursor:pointer;display:block;color:#cfcfcf;padding:11px 20px;font-size:14px;width:100%">Выйти</button>
            </form>
        @endif
    </aside>

    <main>
        @if(session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
        @if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif
        @yield('content')
    </main>
</div>
</body>
</html>
