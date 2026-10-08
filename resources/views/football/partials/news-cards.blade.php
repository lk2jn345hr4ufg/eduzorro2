{{-- $news Collection of TeamNews (team relation loaded); $showTeam bool --}}
@if ($news->isEmpty())
    <p class="empty">{{ __('sport.no_news') }}</p>
@else
    <div class="news-grid">
        @foreach ($news as $item)
            <article class="news-card">
                <a class="news-card-img" href="{{ route('sport.news.show', [$currentLanguage, $item]) }}" tabindex="-1" aria-hidden="true">
                    @if ($item->image_url)
                        <img src="{{ $item->image_url }}" alt="" loading="lazy">
                    @elseif ($item->team?->logo_url)
                        <img class="is-logo" src="{{ $item->team->logo_url }}" alt="" loading="lazy">
                    @endif
                </a>
                <div class="news-card-body">
                    <p class="news-card-meta">
                        @if (! empty($showTeam) && $item->team)
                            <a href="{{ route('sport.team', [$currentLanguage, $item->team]) }}">{{ $item->team->translate('name') }}</a> ·
                        @endif
                        @if ($item->published_at)
                            <time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->translatedFormat('d M Y') }}</time>
                        @endif
                    </p>
                    <h3><a href="{{ route('sport.news.show', [$currentLanguage, $item]) }}">{{ $item->translate('title') }}</a></h3>
                </div>
            </article>
        @endforeach
    </div>
@endif
