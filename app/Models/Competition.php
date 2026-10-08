<?php

namespace App\Models;

use App\Support\HasSeoMeta;
use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;

/**
 * A championship (type = league: the single top domestic league of a country)
 * or a tournament (type = cup: Champions League, World Cup...).
 *
 * Fixtures and standings are linked by the football-data competition code
 * (fixtures.league_code / standings.league_code), which is what the sync
 * already stores, so no backfill of those tables is needed.
 */
class Competition extends Model
{
    use HasSeoMeta;
    use HasTranslations;

    public const LEAGUE = 'league';
    public const CUP    = 'cup';

    /** Slugs that would collide with the literal /{language}/... routes. */
    public const RESERVED_SLUGS = ['team', 'news', 'tools', 'sport', 'search', 'sitemap'];

    protected $guarded = [];

    protected $casts = [
        'name'             => 'array',
        'meta_title'       => 'array',
        'meta_description' => 'array',
        'meta_tabs'        => 'array',
        'is_featured'      => 'boolean',
        'is_active'        => 'boolean',
    ];

    protected static function booted(): void
    {
        // Keep the unique "one league per country" column in step with the
        // type and country, so the rule is enforced by the database index.
        static::saving(function (Competition $competition) {
            $competition->league_country_id = $competition->type === self::LEAGUE
                ? $competition->sport_country_id
                : null;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function country()
    {
        return $this->belongsTo(SportCountry::class, 'sport_country_id');
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function fixtures()
    {
        return $this->hasMany(Fixture::class, 'league_code', 'code');
    }

    public function standings()
    {
        return $this->hasMany(Standing::class, 'league_code', 'code');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeLeagues($query)
    {
        return $query->where('type', self::LEAGUE);
    }

    public function scopeCups($query)
    {
        return $query->where('type', self::CUP);
    }

    public function isLeague(): bool
    {
        return $this->type === self::LEAGUE;
    }

    /** Tabs this competition's page offers; tournaments have no team list or transfers. */
    public function tabs(): array
    {
        return $this->isLeague()
            ? ['dashboard', 'standings', 'fixtures', 'results', 'teams', 'transfers']
            : ['dashboard', 'standings', 'fixtures', 'results'];
    }

    /**
     * The season to show: the newest one we actually hold data for, so a
     * season boundary or a stale setting never leaves the page empty.
     */
    public function currentSeason(): int
    {
        $season = Fixture::where('league_code', $this->code)->max('season')
            ?: Standing::where('league_code', $this->code)->max('season');

        return (int) ($season ?: config('football.season'));
    }

    /** Per-tab meta override (same contract as Team::tabMeta). */
    public function tabMeta(string $tab, string $field, ?string $fallback = null): ?string
    {
        $value = data_get($this->meta_tabs, $tab.'.'.$field.'.'.app()->getLocale());

        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        if ($field === 'heading') {
            return $fallback;
        }

        return $field === 'title'
            ? $this->metaTitle($fallback)
            : $this->metaDescription($fallback);
    }
}
