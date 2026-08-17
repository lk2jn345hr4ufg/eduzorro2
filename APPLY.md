# Transfers tab — proper layout

Reworks the transfers tab (e.g. /ukraine/ru/sport/football/ukraine/dynamo-kyiv/transfers),
which previously dumped ~700 undifferentiated table rows.

## What changed
- **Grouped by transfer season** (Jul-Jun, e.g. "2025/26"), newest season open by
  default, older ones collapsed - so the page opens short instead of endless.
- **Direction badges**: each move is marked as arrival or departure, resolved
  against this team's API id (not by string matching), with in/out counts per season.
- **Only the other club is shown** (with its crest) instead of repeating
  "Dynamo Kyiv" in every row - much better use of width.
- **Deduplicated**: API-Football repeats the same move across windows; rows are
  now unique per player+date+clubs, and self-to-self noise rows are dropped.
- **Transfer type** (loan / fee / free) shown when provided.
- **Readable localized dates** instead of raw 2026-08-12.
- **Mobile layout**: rows reflow into stacked cards under 720px instead of a
  horizontally squeezed 4-column table.

## Files (extract over project root, keep paths)
- resources/views/sport/partials/transfers.blade.php  (rewritten)
- public/css/sport.css                                (transfer styles appended)

No migration, no controller change - same data, better presentation.

## Apply
1. Unzip over the project root (overwrite).
2. php artisan view:clear
3. Hard-refresh the transfers page (the CSS file is cached by the browser).
