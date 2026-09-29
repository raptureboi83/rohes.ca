<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'slug',
    'nav_label',
    'meta_title',
    'meta_description',
    'meta_image',
    'is_active',
    'show_in_nav',
    'is_home',
    'sort_order',
])]
class Page extends Model
{
    /** @use HasFactory<\Database\Factories\PageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_in_nav' => 'boolean',
            'is_home' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Admin URLs use the numeric id; `slug` stays unique and is the
     * lookup key for the public site.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function visibleSections(): HasMany
    {
        return $this->hasMany(Section::class)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->nav_label ?: $this->title;
    }
}
