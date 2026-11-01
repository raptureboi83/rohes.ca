<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'page_id',
    'title',
    'slug',
    'template_key',
    'anchor_id',
    'eyebrow',
    'intro',
    'is_visible',
    'is_system',
    'sort_order',
    'settings',
])]
class Section extends Model
{
    /** @use HasFactory<\Database\Factories\SectionFactory> */
    use HasFactory;

    /**
     * Every layout the public renderer knows how to draw. The admin
     * dropdown is generated from this, so a template key can never be
     * something the frontend has no renderer for.
     */
    public const TEMPLATE_OPTIONS = [
        'hero' => 'Hero banner',
        'stat_grid' => 'Statistics row',
        'card_grid' => 'Card grid',
        'feature_split' => 'Image / text split',
        'text_block' => 'Text block',
        'checklist' => 'Checklist',
        'image_tiles' => 'Image tiles',
        'cta_banner' => 'Call-to-action banner',
        'contact_details' => 'Contact details',
        'contact_form' => 'Contact form',
        'download_list' => 'Document downloads',
        'news_list' => 'News list',
        'rich_text' => 'Free-form HTML',
    ];

    public const SHARED_SETTINGS = [
        'contact_address' => 'contact.address',
        'contact_toll_free' => 'contact.toll_free',
        'contact_phone' => 'contact.phone',
        'contact_mobile' => 'contact.mobile',
        'contact_fax' => 'contact.fax',
        'contact_email' => 'contact.email',
        'contact_hours' => 'contact.hours',
    ];

    /** Plain-language help shown under the template dropdown in the admin. */
    public const TEMPLATE_HELP = [
        'hero' => 'Full-width banner with a headline, intro text, buttons, an optional background photo and a small stats panel. Best as the first section on a page.',
        'stat_grid' => 'A row of large numbers or short facts pulled from the items below.',
        'card_grid' => 'Responsive cards. Each item becomes one card - good for services, news or resources.',
        'feature_split' => 'One image beside one block of text. Use a couple of these to break up a long page.',
        'text_block' => 'A heading and a block of paragraphs. Leave items empty unless you want paragraphs listed.',
        'checklist' => 'A tick list. Each item becomes one line - good for capabilities or requirements.',
        'image_tiles' => 'Small square photos with a caption underneath, for showing equipment or job sites.',
        'cta_banner' => 'A dark band that pushes the visitor towards one action, e.g. "Request a quote".',
        'contact_details' => 'Address, phone, fax, email and a map, all editable from Settings.',
        'contact_form' => 'The on-page enquiry form. Submissions land in the Messages area of the admin.',
        'download_list' => 'List of downloadable files such as brochures and spec sheets.',
        'news_list' => 'Dated entries, newest first. Each item is one news item.',
        'rich_text' => 'Formatted text, tables, images and approved embeds. Scripts, styles and unapproved embeds are removed.',
    ];

    /** @var array<string, array<string, array<string, mixed>>> */
    public const TEMPLATE_SETTINGS = [
        'hero' => [
            'background_image' => ['label' => 'Hero background image', 'type' => 'image', 'hint' => 'Optional. Upload a photo (e.g. a truck) — it will be optimized for web and mobile. Use the visibility slider below to make it subtle so text stays readable.'],
            'background_opacity' => [
                'label' => 'Background image visibility',
                'type' => 'range',
                'min' => 0,
                'max' => 100,
                'step' => 1,
                'default' => 15,
                'hint' => '15% is very faint. Increase it to show more of the photo. Only applies when a background image is selected.',
            ],
            'cta_label' => ['label' => 'Primary button text', 'type' => 'text'],
            'cta_url' => ['label' => 'Primary button link', 'type' => 'text', 'hint' => 'Use a page path or a full external address.'],
            'secondary_label' => ['label' => 'Secondary button text', 'type' => 'text'],
            'secondary_url' => ['label' => 'Secondary button link', 'type' => 'text', 'hint' => 'Use a page path, telephone link, or full external address.'],
        ],
        'card_grid' => [
            'columns' => ['label' => 'Number of columns', 'type' => 'select', 'options' => ['' => 'Automatic', '2' => '2 columns', '3' => '3 columns', '4' => '4 columns']],
            'background' => ['label' => 'Background', 'type' => 'select', 'options' => ['' => 'Default', 'section--sand' => 'Light sand', 'section--green' => 'Green']],
            'center_heading' => ['label' => 'Center the section heading', 'type' => 'checkbox'],
        ],
        'feature_split' => [
            'flip' => ['label' => 'Alternate image and text order', 'type' => 'checkbox'],
        ],
        'cta_banner' => [
            'cta_label' => ['label' => 'Primary button text', 'type' => 'text'],
            'cta_url' => ['label' => 'Primary button link', 'type' => 'text', 'hint' => 'Use a page path or a full external address.'],
            'secondary_label' => ['label' => 'Secondary button text', 'type' => 'text'],
            'secondary_url' => ['label' => 'Secondary button link', 'type' => 'text', 'hint' => 'Use a page path, telephone link, or full external address.'],
        ],
        'contact_details' => [
            'map_embed' => ['label' => 'Map embed', 'type' => 'textarea', 'hint' => 'Paste a Google Maps, YouTube, or Vimeo HTTPS iframe. Other embeds and scripts are removed.'],
            'note' => ['label' => 'Contact note', 'type' => 'textarea'],
        ],
        'contact_form' => [
            'heading_level' => ['label' => 'Heading level', 'type' => 'select', 'options' => ['h2' => 'Section heading', 'h1' => 'Page heading'], 'group' => 'Form options'],
            'interests' => ['label' => 'Enquiry options', 'type' => 'textarea', 'hint' => 'One option per line. Leave blank to omit the list.', 'group' => 'Form options'],
            'contact_address' => ['label' => 'Office address', 'type' => 'textarea', 'group' => 'Contact details shared across the site'],
            'contact_toll_free' => ['label' => 'Toll-free number', 'type' => 'text', 'group' => 'Contact details shared across the site'],
            'contact_phone' => ['label' => 'Local phone', 'type' => 'text', 'group' => 'Contact details shared across the site'],
            'contact_mobile' => ['label' => 'Mobile phone', 'type' => 'text', 'group' => 'Contact details shared across the site'],
            'contact_fax' => ['label' => 'Fax', 'type' => 'text', 'group' => 'Contact details shared across the site'],
            'contact_email' => ['label' => 'Email address', 'type' => 'text', 'group' => 'Contact details shared across the site'],
            'contact_hours' => ['label' => 'Opening hours', 'type' => 'textarea', 'group' => 'Contact details shared across the site'],
        ],
        'rich_text' => [
            'html' => ['label' => 'Formatted content', 'type' => 'textarea', 'hint' => 'Basic HTML is allowed. Scripts, styles, unsafe links and unapproved embeds are removed.'],
        ],
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
            'settings' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SectionItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Read a value out of the section's JSON settings blob using dot
     * notation, with a fallback. Keeps templates free of null checks.
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        $value = data_get($this->settings, $key);

        return ($value === null || $value === '') ? $default : $value;
    }

    public function getTemplateLabelAttribute(): string
    {
        return self::TEMPLATE_OPTIONS[$this->template_key] ?? $this->template_key;
    }

    public function getHasTemplateAttribute(): bool
    {
        return array_key_exists($this->template_key, self::TEMPLATE_OPTIONS);
    }
}
