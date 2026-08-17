# General sports news section (feed of all team news)

Adds a central sports-news feed that aggregates news from every team, plus a
dedicated page per article.

## URLs (localized)
- /{region}/{lang}/sport/news            all sports news, newest first, paginated
- /{region}/{lang}/sport/news/{slug}     single article page (indexable)

Links to the feed are added on: the Sport index page, the localized home Sport
section, and each region card on the global home. Team News-tab items and feed
items now link to the internal article page.

## Files (extract over project root, keep paths)
- app/Http/Controllers/SportNewsController.php   (new)
- routes/web.php                                 (modified: news routes before the sport wildcard)
- resources/views/sport/news/index.blade.php     (new: feed)
- resources/views/sport/news/show.blade.php      (new: article)
- resources/views/sport/partials/news.blade.php  (modified: titles link to article page)
- resources/views/sport/index.blade.php          (modified: news link)
- resources/views/region-language.blade.php      (modified: news chip)
- resources/views/home.blade.php                 (modified: news chip per region)
- public/css/sport.css                           (modified: feed/pager/article styles)
- lang/{en,uk,ru,es}/sport.php                   (modified: news labels)

Depends on the sport module + team_news already installed. No migration.

## Apply
1. Unzip over the project root (overwrite).
2. php artisan optimize:clear
3. Visit /{region}/{lang}/sport/news.

## Notes
- The feed reads team_news (active + published), so once you sync/rewrite news it
  shows up automatically, in the visitor language (Gemini translations included).
- Article slugs already carry a short URL hash, so they are effectively unique for
  the global /sport/news/{slug} route.
