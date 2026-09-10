# Video & lecture study tools (4 new tools)

Adds a "Video & lectures" category to /tools with four tools that help people
work with lecture videos — linking, planning and note-taking — without copying
the videos themselves.

## The tools
- **/tools/youtube-timestamp** — paste a video URL, set h:m:s, get a link that
  opens at that moment plus the ready `<iframe>` embed code. Accepts watch?v=,
  youtu.be, /embed/, /shorts/ and bare ids. Pure browser JS, nothing is fetched.
- **/tools/video-study-planner** — paste the durations of a course's videos and
  see the total, the time at your playback speed, the realistic effort with
  note-taking overhead, how many days it takes at N minutes/day, and the finish
  date. Accepts 12:30, 1:04:20, "12m 30s" or plain minutes. Also browser-only.
- **/tools/video-notes-ai** — the visitor pastes notes THEY wrote and gets back a
  structured outline, self-check questions, or a spaced-revision plan. Uses the
  Gemini key already configured for news. Rate limited to 10 requests/hour/IP.
- **/tools/offline-video-guide** — a short reference page on legal ways to keep
  lectures available offline (app offline mode, Premium, asking the author,
  course platforms with built-in downloads).

## Files (extract over project root, keep paths)
- resources/views/tools/partials/{youtube-timestamp,video-study-planner,video-notes-ai,offline-video-guide}.blade.php (new)
- database/seeders/VideoToolSeeder.php        (new)
- app/Http/Controllers/ToolAiController.php   (new: the AI endpoint)
- app/Services/AI/GeminiClient.php            (modified: added complete())
- routes/web.php                              (modified: POST tools/video-notes/generate)
- resources/views/layouts/app.blade.php       (modified: csrf-token meta, needed by the AI tool)
- app/Filament/Resources/ToolResource.php     (modified: "Video & lectures" category)
- public/css/tools.css                        (modified: styles for the new tools)
- lang/{en,uk,ru,es}/tools.php                (modified: 43 new strings + category)

## Apply — local
```
unzip -o ~/Downloads/video-tools.zip -d /tmp/vid-unzip
cp -a /tmp/vid-unzip/video-tools/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/vid-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
php artisan db:seed --class=Database\\Seeders\\VideoToolSeeder
git add .
git commit -m "Add video and lecture study tools"
git push
```

## Apply — server
```
cd ~/laravel-app
git pull origin main
php artisan optimize:clear
php artisan db:seed --class=Database\\Seeders\\VideoToolSeeder --force
```

No migration — reuses the existing `tools` table.

## Notes
- Three of the four tools run entirely in the browser; only the notes organiser
  makes a server call, and only when the visitor presses the button.
- The AI tool needs the Gemini key (Admin → Sport → Settings → Gemini). Without
  it the tool answers with a clear "not configured" message instead of failing.
- The rate limit is per IP per hour and lives in ToolAiController; raise it there
  if the tool proves popular.
- Deliberately not included: a video downloader. It breaks YouTube's terms,
  attracts DMCA complaints, is rejected by ad networks, and is a common reason
  for shared hosts to suspend an account — a real risk to the whole domain.
