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
     * tool = one view + one row. Rows without a view fall back to their text.
     */
    public function viewName(): string
    {
        return 'tools.partials.'.$this->slug;
    }

    public function hasView(): bool
    {
        return view()->exists($this->viewName());
    }
}
