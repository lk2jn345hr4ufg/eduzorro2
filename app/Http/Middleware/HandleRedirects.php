<?php

namespace App\Http\Middleware;

use App\Models\Redirect as RedirectModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Runs before routing (see bootstrap/app.php), so it also catches retired
 * URLs that no longer match any route in the app — the main use case for a
 * 301 module. Active redirects are cached for 5 minutes as plain arrays
 * (never Eloquent objects — caching live model instances couples the cache
 * payload to exact class definitions and can break with "incomplete
 * object" unserialize errors if anything about the class drifts). The
 * Redirect model busts this cache on save/delete, so admin edits take
 * effect immediately.
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $path  = RedirectModel::normalizePath($request->path());
        $match = $this->match($path);

        if ($match) {
            RedirectModel::whereKey($match['id'])->update([
                'hits'         => DB::raw('hits + 1'),
                'last_hit_at'  => now(),
            ]);

            return redirect($this->buildTarget($match, $path), $match['status_code']);
        }

        return $next($request);
    }

    /**
     * @return array{id:int,from_path:string,to_path:string,match_type:string,status_code:int}|null
     */
    protected function match(string $path): ?array
    {
        $redirects = collect(Cache::remember(RedirectModel::CACHE_KEY, 300, function () {
            return RedirectModel::active()->get()
                ->map(fn (RedirectModel $r) => [
                    'id'          => $r->id,
                    'from_path'   => $r->from_path,
                    'to_path'     => $r->to_path,
                    'match_type'  => $r->match_type,
                    'status_code' => $r->status_code,
                ])
                ->all();
        }));

        $exact = $redirects->first(fn (array $r) => $r['match_type'] === 'exact' && $r['from_path'] === $path);
        if ($exact) {
            return $exact;
        }

        // Prefix rules: longest matching prefix wins, so a more specific
        // rule (e.g. /old-region/en/language-learning) beats a broader one
        // (e.g. /old-region) when both match.
        return $redirects
            ->filter(fn (array $r) => $r['match_type'] === 'prefix' && str_starts_with($path, $r['from_path']))
            ->sortByDesc(fn (array $r) => strlen($r['from_path']))
            ->first();
    }

    protected function buildTarget(array $redirect, string $path): string
    {
        if ($redirect['match_type'] === 'prefix') {
            $remainder = substr($path, strlen($redirect['from_path']));

            return rtrim($redirect['to_path'], '/') . $remainder;
        }

        return $redirect['to_path'];
    }
}
