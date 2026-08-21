<?php

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    use HasTranslations;

    protected $guarded = [];

    protected $casts = [
        'name'        => 'array',
        'description' => 'array',
        'intro'       => 'array',
        'is_active'   => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Each tool renders its own Blade partial, resolved from the slug, so a new
     * tool = one view + one row. Tools defined in config/math_tools.php instead
     * share the generic engine view, which builds itself from that config.
     */
    public function viewName(): string
    {
        $own = 'tools.partials.'.$this->slug;

        if (view()->exists($own)) {
            return $own;
        }

        return config('math_tools.'.$this->slug) ? 'tools.partials.math' : $own;
    }

    public function hasView(): bool
    {
        return view()->exists($this->viewName());
    }
}
