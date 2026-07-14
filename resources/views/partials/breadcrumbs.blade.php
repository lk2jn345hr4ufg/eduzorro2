{{-- Expects $breadcrumbs = [['label' => ..., 'url' => ... (omit/empty for current)], ...] --}}
@php($items = $breadcrumbs ?? [])

@if (!empty($items))
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <ol>
            @foreach ($items as $item)
                <li>
                    @if (!empty($item['url']) && !$loop->last)
                        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                    @else
                        <span aria-current="page">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>

    <script type="application/ld+json">
        {!! json_encode(\App\Support\Seo::breadcrumbList($items), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endif
