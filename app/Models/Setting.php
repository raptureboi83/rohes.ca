<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'group', 'value'])]
class Setting extends Model
{
    /**
     * Grouping used to organise the admin settings screen. Keeping this on
     * the row (rather than hardcoded in the controller) means new fields
     * can be introduced without touching the settings form.
     */
    public const GROUPS = [
        'general' => 'General',
        'contact' => 'Contact details',
        'branding' => 'Branding',
        'seo' => 'SEO',
        'homepage' => 'Homepage',
        'social' => 'Social & embeds',
        'legal' => 'Legal & compliance',
    ];

    public const SOCIAL_NETWORKS = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'x' => 'X',
    ];

    public function getIsEmptyAttribute(): bool
    {
        return $this->value === null || $this->value === '';
    }
}
