@extends('admin.layout')

@section('title', 'Site settings')

@section('content')
<div class="admin-head">
    <div>
        <h1>Site settings</h1>
        <p>These values appear across the whole website. Page-specific wording is edited on the page itself.</p>
        <p>To edit page headings, paragraphs, links and photos, open <a href="{{ route('admin.pages.index') }}">Pages</a> and choose Edit content.</p>
    </div>
</div>

{{--
    Field definitions live here rather than in the controller so that adding a
    setting is a one-line change. Each entry needs a label; `type` defaults to
    text. A blank value is stored as "no override", so clearing a box restores
    the built-in default.
--}}
@php
    $field = function (string $key, string $label, string $type = 'text', string $hint = '') {
        return compact('key', 'label', 'type', 'hint');
    };

    $groups = [
        'general' => [
            'title' => 'General',
            'blurb' => 'The business name and how it is described.',
            'fields' => [
                $field('site.name', 'Business name'),
                $field('site.short_name', 'Short name', 'text', 'Used in the admin sidebar and as a compact logo label.'),
                $field('site.tagline', 'Tagline'),
                $field('site.footer_blurb', 'Footer description', 'textarea', 'A sentence or two shown at the bottom of every page.'),
            ],
        ],
        'branding' => [
            'title' => 'Branding',
            'blurb' => 'Logo and colour identity.',
            'fields' => [
                $field('site.logo_path', 'Logo file', 'text', 'Path to the logo, e.g. /media/legacy/logo.png. Leave blank to show the short name instead.'),
            ],
        ],
        'contact' => [
            'title' => 'Contact details',
            'blurb' => 'Shown on the contact page and in the footer.',
            'fields' => [
                $field('contact.toll_free', 'Toll Free'),
                $field('contact.phone', 'Local'),
                $field('contact.mobile', 'Mobile', 'text', 'Optional. Leave blank and it is hidden everywhere on the site.'),
                $field('contact.fax', 'Fax', 'text', 'Optional. Leave blank and it is hidden everywhere on the site.'),
                $field('contact.email', 'Email address'),
                $field('contact.address', 'Street address', 'textarea', 'Used on the contact page and for search engines. Include the postal code.'),
                $field('contact.hours', 'Opening hours', 'textarea', 'For example: Monday to Friday, 8:00am - 5:00pm'),
            ],
        ],
        'social' => [
            'title' => 'Social profiles',
            'blurb' => 'Only profiles with a saved link appear as icons in the header and footer.',
            'fields' => [
                $field('social.facebook', 'Facebook profile URL', 'text', 'Full HTTPS address. Leave blank to hide the icon.'),
                $field('social.instagram', 'Instagram profile URL', 'text', 'Full HTTPS address. Leave blank to hide the icon.'),
                $field('social.linkedin', 'LinkedIn profile URL', 'text', 'Full HTTPS address. Leave blank to hide the icon.'),
                $field('social.youtube', 'YouTube channel URL', 'text', 'Full HTTPS address. Leave blank to hide the icon.'),
                $field('social.x', 'X profile URL', 'text', 'Full HTTPS address. Leave blank to hide the icon.'),
            ],
        ],
        'seo' => [
            'title' => 'Search engines',
            'blurb' => 'The defaults used when a page does not set its own.',
            'fields' => [
                $field('site.seo_title', 'Default page title', 'text', 'Used when a page has no title of its own.'),
                $field('site.seo_description', 'Default description', 'textarea', 'The grey summary in search results. Around 150 characters.'),
                $field('site.google_analytics_id', 'Google Analytics ID', 'text', 'Optional. Format looks like G-XXXXXXXXXX.'),
            ],
        ],
        'legal' => [
            'title' => 'Legal and compliance',
            'blurb' => 'Details a regulated wastewater business is expected to display.',
            'fields' => [
                $field('legal.license_number', 'Licence number', 'text', 'For example, the MOE approval number on the licence.'),
            ],
        ],
    ];
@endphp

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    @method('PUT')

    @foreach ($groups as $groupKey => $group)
        <div class="admin-card settings-group" style="margin-bottom:1.25rem">
            <div class="admin-card__head">
                <div>
                    <h2>{{ $group['title'] }}</h2>
                    <p class="hint" style="margin:.2rem 0 0">{{ $group['blurb'] }}</p>
                </div>
            </div>
            <div class="admin-card__body">
                @foreach ($group['fields'] as $f)
                    @php
                        $oldSettings = old('settings', []);
                        $value = is_array($oldSettings) && array_key_exists($f['key'], $oldSettings)
                            ? $oldSettings[$f['key']]
                            : ($settings[$f['key']]->value ?? '');
                    @endphp
                    <div class="afield">
                        <label for="s-{{ $f['key'] }}">{{ $f['label'] }}</label>

                        @if ($f['key'] === 'site.logo_path')
                            @php $logoPath = $value; @endphp
                            <div x-data="{ imagePath: @js($logoPath) }">
                                <input type="hidden" name="settings[{{ $f['key'] }}]" value="{{ $logoPath }}" :value="imagePath">
                                <select class="ainput" id="s-{{ $f['key'] }}" x-model="imagePath">
                                    <option value="">No logo image</option>
                                    @if ($logoPath && ! $availableImages->contains(fn ($image) => $image->url === $logoPath))
                                        <option value="{{ $logoPath }}">Current image (not in the media library)</option>
                                    @endif
                                    @foreach ($availableImages as $image)
                                        <option value="{{ $image->url }}">{{ $image->title ?: $image->original_name }}</option>
                                    @endforeach
                                </select>
                                <input class="ainput" type="text" style="margin-top:.5rem" aria-label="Custom logo path"
                                       x-model="imagePath" placeholder="Or enter a custom image path">
                            </div>
                        @elseif ($f['type'] === 'textarea')
                            <textarea class="atextarea" id="s-{{ $f['key'] }}" name="settings[{{ $f['key'] }}]" rows="3">{{ $value }}</textarea>
                        @else
                            <input class="ainput" type="text" id="s-{{ $f['key'] }}" name="settings[{{ $f['key'] }}]"
                                   value="{{ $value }}" placeholder="{{ str_starts_with($f['key'], 'social.') ? 'https://…' : '' }}">
                        @endif

                        @if ($f['hint'])<p class="hint">{{ $f['hint'] }}</p>@endif
                        @error('settings.' . $f['key'])<p class="aerror">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="admin-card">
        <div class="admin-card__body form-actions" style="border:0">
            <p class="hint" style="margin:0">
                Clearing a box removes the value so the built-in default applies again.
            </p>
            <button type="submit" class="abtn abtn--primary">Save settings</button>
        </div>
    </div>
</form>
@endsection
