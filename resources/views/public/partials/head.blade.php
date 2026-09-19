{{-- Общие стили и SEO-мета публичной части.
     Приоритет источников заголовка/описания:
       1) metaTitle / metaDescription (из контроллера — шаблоны config/seo.php)
       2) title / description (переданы во @include на странице)
       3) значения по умолчанию --}}
@php
    $pageTitle = ($metaTitle ?? null) ?: ($title ?? 'LiverpoolIn — новости «Ливерпуля»');
    $pageDesc  = ($metaDescription ?? null) ?: ($description ?? 'Новости, расписание, турнирная таблица и трансферы «Ливерпуля» — на русском, каждый день.');
    $pageDesc  = \Illuminate\Support\Str::limit(trim(strip_tags($pageDesc)), 200);
    $canonical = $canonical ?? url()->current();
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $pageTitle }}</title>

<meta name="description" content="{{ $pageDesc }}">
<link rel="canonical" href="{{ $canonical }}">

{{-- Open Graph --}}
<meta property="og:type" content="{{ ($ogType ?? null) ?: 'website' }}">
<meta property="og:site_name" content="LiverpoolIn">
<meta property="og:locale" content="ru_RU">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDesc }}">
<meta property="og:url" content="{{ $canonical }}">
@isset($image)<meta property="og:image" content="{{ $image }}">@endisset
<meta name="twitter:card" content="{{ isset($image) ? 'summary_large_image' : 'summary' }}">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
    :root{
        --red:#c8102e; --wine:#6a0f1e; --wine-2:#4c0a16;
        --paper:#f6f3ee; --card:#fffdfa; --ink:#1a1413; --muted:#6f645d;
        --gold:#f5b22d; --line:#e4ded4;
        --display:'Anton',Impact,sans-serif; --sans:'Archivo',system-ui,sans-serif;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:var(--sans);background:var(--paper);color:var(--ink);line-height:1.5;-webkit-font-smoothing:antialiased}
    a{color:inherit;text-decoration:none}
    img{display:block;width:100%;height:100%;object-fit:cover}
    .wrap{max-width:1120px;margin:0 auto;padding:0 20px}

    .masthead{background:var(--paper);border-bottom:3px solid var(--red)}
    .masthead .bar{max-width:1120px;margin:0 auto;padding:0 20px;display:flex;align-items:center;gap:24px;height:72px}
    .logo{font-family:var(--display);font-size:29px;letter-spacing:.5px;line-height:1;text-transform:uppercase}
    .logo b{color:var(--red)} .logo span{color:var(--gold);-webkit-text-stroke:1px var(--wine)}
    .nav{margin-left:auto;display:flex;gap:22px;font-weight:700;text-transform:uppercase;font-size:12.5px;letter-spacing:.8px;flex-wrap:wrap}
    .nav a{padding:5px 0;border-bottom:2px solid transparent;white-space:nowrap}
    .nav a:hover,.nav a.on{border-color:var(--gold)}

    .catbar{background:var(--card);border-bottom:1px solid var(--line)}
    .catbar .in{max-width:1120px;margin:0 auto;padding:0 20px;display:flex;gap:8px;align-items:center;overflow-x:auto;min-height:48px}
    .chip{font-size:12px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;padding:6px 12px;border-radius:99px;border:1px solid var(--line);color:var(--muted);white-space:nowrap}
    .chip:hover{border-color:var(--red);color:var(--red)}
    .chip.on{background:var(--red);border-color:var(--red);color:#fff}

    .tag{display:inline-block;font-size:10px;font-weight:800;letter-spacing:1.4px;text-transform:uppercase;padding:4px 9px;border-radius:2px;color:#fff}

    .sec-head{display:flex;align-items:center;gap:14px;margin:8px 0 18px}
    .sec-head h2{font-family:var(--display);font-weight:400;text-transform:uppercase;font-size:22px;letter-spacing:1px}
    .sec-head .rule{flex:1;height:3px;background:var(--red)}

    .grid{display:grid;grid-template-columns:1fr 1fr;gap:22px}
    .story{background:var(--card);border:1px solid var(--line);border-radius:6px;overflow:hidden;display:flex;flex-direction:column;transition:transform .15s ease,box-shadow .15s ease}
    .story:hover{transform:translateY(-3px);box-shadow:0 10px 24px rgba(26,20,19,.10)}
    .story .thumb{aspect-ratio:16/10;position:relative;background:linear-gradient(140deg,var(--red),var(--wine))}
    .story .thumb .mark{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:var(--display);color:rgba(255,255,255,.22);font-size:34px}
    .story .thumb .tag{position:absolute;left:10px;top:10px}
    .story .body{padding:15px 16px 17px;display:flex;flex-direction:column;gap:8px;flex:1}
    .kicker{font-weight:800;font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:var(--red)}
    .story h3{font-size:18px;line-height:1.16;font-weight:800;letter-spacing:-.2px}
    .story .ex{font-size:14px;color:var(--muted);flex:1}
    .story .time{font-size:12px;color:var(--muted);font-weight:600}

    .empty{background:var(--card);border:1px dashed var(--line);border-radius:6px;padding:26px;color:var(--muted);font-size:15px}

    footer{background:var(--wine-2);color:rgba(255,255,255,.8);margin-top:44px}
    footer .in{max-width:1120px;margin:0 auto;padding:28px 20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap}
    footer .ynwa{font-family:var(--display);text-transform:uppercase;color:var(--gold);letter-spacing:1px;font-size:18px}
    footer small{font-size:12px;margin-left:auto}

    @media(max-width:820px){.grid{grid-template-columns:1fr}.nav{display:none}}
    @media(prefers-reduced-motion:reduce){*{transition:none!important}}
    :focus-visible{outline:3px solid var(--gold);outline-offset:2px}
</style>
