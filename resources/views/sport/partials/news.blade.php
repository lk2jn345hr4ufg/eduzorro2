@if ($news->isEmpty())
    <p>{{ __('sport.no_news') }}</p>
@else
    <div class="news-list">
        @foreach ($news as $item)
            <article class="news-item">
                @if ($item->image_url)
                    <a href="{{ route('sport.news.show', [$currentRegion, $currentLanguage, $item]) }}">
                        <img src="{{ $item->image_url }}" alt="" loading="lazy">
                    </a>
                @endif
                <div>
                    <h3>
                        <a href="{{ route('sport.news.show', [$currentRegion, $currentLanguage, $item]) }}">
                            {{ $item->translate('title') }}
                        </a>
                    </h3>
                    @if ($item->published_at)
                        <time datetime="{{ $item->published_at->toDateString() }}">
                            {{ $item->published_at->translatedFormat('d M Y') }}
                        </time>
                    @endif
                    @if ($ex = $item->translate('excerpt'))
                        <p>{{ \Illuminate\Support\Str::limit($ex, 160) }}</p>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endif
