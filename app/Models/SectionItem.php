<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'section_id',
    'item_type',
    'title',
    'subtitle',
    'body',
    'image_path',
    'image_alt',
    'link_url',
    'link_text',
    'sort_order',
    'data',
])]
class SectionItem extends Model
{
    /** @use HasFactory<\Database\Factories\SectionItemFactory> */
    use HasFactory;

    /**
     * Presentation hints. The containing section's template decides the
     * layout; item_type only tweaks how an individual item is drawn.
     */
    public const ITEM_TYPES = [
        'default' => 'Default',
        'service' => 'Service',
        'news' => 'News item',
        'link' => 'Resource link',
        'document' => 'Document',
        'stat' => 'Statistic',
        'feature' => 'Feature',
        'checklist' => 'Checklist line',
    ];

    /** @var array<string, array<string, array<string, mixed>>> */
    public const DATA_FIELDS = [
        'news' => [
            'date' => ['label' => 'Date or date label', 'type' => 'text', 'hint' => 'Use a date, “Company news”, or “Industry background”.'],
            'is_archived' => ['label' => 'Mark as archived/background material', 'type' => 'checkbox', 'hint' => 'Archived links open in a new tab.'],
        ],
        'stat' => [
            'value' => ['label' => 'Displayed value', 'type' => 'text', 'hint' => 'Used when the item has no title.'],
        ],
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'data' => 'array',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::ITEM_TYPES[$this->item_type] ?? $this->item_type;
    }

    /** Extra values stored in the JSON `data` blob, with dot-notation access. */
    public function meta(string $key, mixed $default = null): mixed
    {
        $value = data_get($this->data, $key);

        return ($value === null || $value === '') ? $default : $value;
    }

    /**
     * True when the item should be rendered as a link to another page
     * rather than an outbound URL.
     */
    public function isInternalLink(): bool
    {
        $url = (string) $this->link_url;

        return $url !== '' && ! preg_match('#^(https?:|mailto:|tel:|#)#i', $url);
    }
}
