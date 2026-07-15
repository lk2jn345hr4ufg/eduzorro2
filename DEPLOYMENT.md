# Deploying Eduzorro to shared hosting

You have SSH access and a MySQL database — that's enough to deploy this
properly regardless of which control panel (cPanel/Plesk/DirectAdmin/other)
sits on top. This guide is panel-agnostic; the one place panels differ
(setting the document root) is called out explicitly below.

## 0. Before you start

- **PHP version**: this app needs PHP 8.2+. Check what's active over SSH:
  ```bash
  php -v
  ```
  If it's older, look for a "PHP version" / "MultiPHP Manager" section in
  your host's panel and switch it — every major panel has one, just under a
  different name.
- **Composer on the server**: many shared hosts restrict it (memory limits,
  disabled `exec()`), especially for a dependency set this size (Laravel +
  Filament + Livewire). Rather than fight that, build `vendor/` **locally**
  (where you know it already works) and deploy it as part of the upload.
  Test whether the server's Composer works later, as a nice-to-have, not a
  blocker.

## 1. Build production dependencies locally

On your Mac:

```bash
cd ~/Herd/eduzorro
composer install --no-dev --optimize-autoloader
```

`--no-dev` strips testing/dev-only packages, `--optimize-autoloader` makes
class loading faster in production. This regenerates `vendor/` — don't
worry, it's already gitignored, this step just needs to happen once before
each deploy (or automate it later with a proper CI pipeline).

## 2. Get the code onto the server

**Method A — git (recommended, since you already have a repo):**

```bash
ssh [email protected]

# pick a location OUTSIDE the public web root — see step 4 for why
cd ~
git clone [email protected]:YOUR_USERNAME/eduzorro.git eduzorro
cd eduzorro
```

If the server can't reach GitHub over SSH (some shared hosts block outbound
git), use HTTPS instead: `git clone https://github.com/YOUR_USERNAME/eduzorro.git`.

**Method B — upload directly (no git on the server):**

From your Mac, `rsync` the whole project including the `vendor/` you just
built:

```bash
rsync -avz --exclude='.git' --exclude='node_modules' \
    ~/Herd/eduzorro/ [email protected]:~/eduzorro/
```

Either way, you should end up with the full app (including `vendor/`) at
`~/eduzorro` on the server.

## 3. Server-side environment

Still over SSH, in `~/eduzorro`:

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME=Eduzorro
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db_name       # from your host's MySQL setup
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

Two things worth being deliberate about:

- **Switch off SQLite.** Locally you've been running on
  `database/database.sqlite` — fine for development, not what you want in
  production on shared hosting (file-locking issues under concurrent
  requests, and it's what the original migrations were designed against
  anyway). The `DB_CONNECTION=mysql` block above is the real production
  config.
- **`APP_DEBUG=false` is not optional.** With it `true`, any error dumps
  full stack traces (file paths, query contents, sometimes `.env` values)
  straight to visitors. Every error you've pasted me this whole project had
  `APP_DEBUG=true` — great for us debugging together, dangerous in
  production.

## 4. Document root: exposing only `public/`

Laravel's entire `app/`, `.env`, `database/`, `vendor/` etc. must **never**
be reachable by a URL — only the `public/` folder's contents should be
web-accessible. How you achieve that depends on what your panel allows:

**If your panel lets you point the domain's document root at any folder**
(look for "Document Root" in the domain/subdomain settings): point it
directly at `~/eduzorro/public`. This is the clean, correct setup — do this
if it's available. Nothing else in this section applies if so.

**If your panel forces the document root to stay at `public_html`** (the
common shared-hosting constraint, e.g. classic cPanel): keep the app outside
`public_html` (which you already did — it's at `~/eduzorro`), then copy
`public/`'s contents into `public_html` and patch the two path references
inside `index.php`:

```bash
# from ~/eduzorro
cp -r public/* ~/public_html/
```

Then edit `~/public_html/index.php` — change these two lines:

```php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

to point one level further up, at the real app location:

```php
require __DIR__.'/../eduzorro/vendor/autoload.php';
$app = require_once __DIR__.'/../eduzorro/bootstrap/app.php';
```

(Adjust `../eduzorro/` if your actual path differs.) This is the standard,
widely-used pattern for Laravel on shared hosting without document-root
control — it works, it's just an extra step instead of the clean option
above.

## 5. Install, migrate, and import

Back in `~/eduzorro` over SSH:

```bash
# only if Composer works on the server and you skipped building vendor/ locally
# composer install --no-dev --optimize-autoloader

php artisan migrate --force
php artisan import:wordpress
php artisan import:redirects
php artisan import:wordpress-regions
```

`--force` is required because `APP_ENV=production` normally blocks
migrations without it (a safety rail, not a bug).

Create your Filament admin login (or rely on the one the seeder already
creates — see `FILAMENT-ADMIN.md` — but change that password immediately in
production):

```bash
php artisan tinker --execute="App\Models\User::where('email','[email protected]')->update(['password'=>Hash::make('a-real-password')]);"
```

## 6. Production performance caching

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Important**: any time you change `.env` after this, you must run
`php artisan config:cache` again — with config cached, Laravel stops reading
`.env` directly and only reads the cached file. Forgetting this is a classic
"why isn't my env change taking effect" trap.

## 7. File permissions

```bash
chmod -R 755 storage bootstrap/cache
```

If you get permission errors writing logs/cache/sessions, your web server's
user (often `www-data`, `nobody`, or your own shared-hosting user — varies
by host) needs write access to `storage/` and `bootstrap/cache/`. Ask your
host's support what user PHP runs as if `755` isn't enough; some hosts need
`775` with a specific group.

## 8. SSL

Almost every host offers free Let's Encrypt SSL through the panel — look for
"SSL/TLS" in yours. Once issued, make sure `APP_URL` in `.env` uses
`https://`, then re-run `php artisan config:cache`.

## 9. Verify

- Visit your domain — should show the Eduzorro home page with Ukraine/Kazakhstan.
- Visit `/admin` — Filament login should load.
- Visit an old redirect URL, e.g. `/courses/{a-real-old-slug}` — should 301
  correctly.
- Check `storage/logs/laravel.log` over SSH if anything looks broken:
  ```bash
  tail -50 storage/logs/laravel.log
  ```

## Redeploying after future changes

Once this is working, your update loop is:

```bash
# on your Mac
git add . && git commit -m "..." && git push

# on the server
cd ~/eduzorro
git pull
composer install --no-dev --optimize-autoloader   # only if composer.json changed
php artisan migrate --force                        # only if new migrations
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
