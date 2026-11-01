@php $fieldPrefix = $fieldPrefix ?? ''; @endphp

<div class="afield">
    <label for="{{ $fieldPrefix }}title">Heading for this part <span aria-hidden="true">*</span></label>
    <input class="ainput" type="text" id="{{ $fieldPrefix }}title" name="title" value="{{ old('title', $section->title) }}" required
           placeholder="e.g. Our services">
    <p class="hint">The heading visitors see above this part of the page.</p>
    @error('title')<p class="aerror">{{ $message }}</p>@enderror
</div>

<div class="afield">
    <label for="{{ $fieldPrefix }}eyebrow">Small heading (optional)</label>
    <input class="ainput" type="text" id="{{ $fieldPrefix }}eyebrow" name="eyebrow" value="{{ old('eyebrow', $section->eyebrow) }}"
           placeholder="e.g. What we do">
    <p class="hint">A short line shown above the section title.</p>
    @error('eyebrow')<p class="aerror">{{ $message }}</p>@enderror
</div>

<div class="afield">
    <label for="{{ $fieldPrefix }}intro">Short introduction (optional)</label>
    <textarea class="atextarea" id="{{ $fieldPrefix }}intro" name="intro" rows="3"
              placeholder="One or two sentences introducing this section.">{{ old('intro', $section->intro) }}</textarea>
    <p class="hint">This appears under the section title.</p>
    @error('intro')<p class="aerror">{{ $message }}</p>@enderror
</div>

<fieldset class="afield" x-data="{
        tpl: @js(old('template_key', $section->template_key)),
        help: @js(App\Models\Section::TEMPLATE_HELP),
    }">
    <legend style="font-weight:600;margin-bottom:.4rem">Content and layout</legend>

    @foreach (App\Models\Section::TEMPLATE_SETTINGS as $templateKey => $fields)
        @if ($fields)
            <div x-show="tpl === '{{ $templateKey }}'" x-cloak style="margin-top:1rem">
                <h3 style="font-size:1rem;margin-bottom:.75rem">{{ App\Models\Section::TEMPLATE_OPTIONS[$templateKey] }} options</h3>
                @php $previousGroup = null; @endphp
                @foreach ($fields as $key => $field)
                    @if (($field['group'] ?? null) && $field['group'] !== $previousGroup)
                        <h4 style="font-size:.95rem;margin:1rem 0 .5rem">{{ $field['group'] }}</h4>
                        @if ($field['group'] === 'Contact details shared across the site')
                            <p class="hint">These values appear on this page and in the site footer. Changes update the shared Contact details settings.</p>
                        @endif
                        @php $previousGroup = $field['group']; @endphp
                    @endif
                    @php
                        $sharedSettingKey = App\Models\Section::SHARED_SETTINGS[$key] ?? null;
                        $storedValue = $sharedSettingKey
                            ? setting($sharedSettingKey, '')
                            : $section->setting($key, $field['default'] ?? '');
                        $value = old('settings.'.$key, $storedValue);
                    @endphp
                    @if ($field['type'] === 'image')
                        <div class="afield" x-data="{ imagePath: @js($value), imageError: false, previewUrl() { return this.imagePath ? new URL(this.imagePath, @js(url('/'))).toString() : '' } }">
                            <label for="{{ $fieldPrefix }}setting-{{ $templateKey }}-{{ $key }}">{{ $field['label'] }}</label>
                            <select class="ainput" id="{{ $fieldPrefix }}setting-{{ $templateKey }}-{{ $key }}"
                                    name="settings[{{ $key }}]" x-model="imagePath" :disabled="tpl !== '{{ $templateKey }}'">
                                <option value="">No background image</option>
                                @if ($value && ! $availableImages->contains(fn ($image) => $image->url === $value))
                                    <option value="{{ $value }}" selected>Current image (not in the media library)</option>
                                @endif
                                @foreach ($availableImages as $image)
                                    <option value="{{ $image->url }}" @selected($value === $image->url)>
                                        {{ $image->title ?: $image->original_name }}{{ $image->width ? ' ('.$image->width.' x '.$image->height.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @include('admin.partials.image-preview')
                            @if (! empty($field['hint']))<p class="hint">{{ $field['hint'] }}</p>@endif
                            @error('settings.'.$key)<p class="aerror">{{ $message }}</p>@enderror
                        </div>
                    @elseif ($field['type'] === 'checkbox')
                        <div class="check">
                            <input type="hidden" name="settings[{{ $key }}]" value="0" :disabled="tpl !== '{{ $templateKey }}'">
                            <input type="checkbox" id="{{ $fieldPrefix }}setting-{{ $templateKey }}-{{ $key }}" name="settings[{{ $key }}]" value="1"
                                   :disabled="tpl !== '{{ $templateKey }}'" @checked((bool) $value)>
                            <label for="{{ $fieldPrefix }}setting-{{ $templateKey }}-{{ $key }}">{{ $field['label'] }}</label>
                        </div>
                    @elseif ($field['type'] === 'range')
                        @php
                            $rangeMin = (int) ($field['min'] ?? 0);
                            $rangeMax = (int) ($field['max'] ?? 100);
                            $rangeValue = max($rangeMin, min($rangeMax, (int) $value));
                        @endphp
                        <div class="afield" x-data="{ rangeValue: @js($rangeValue) }">
                            <label for="{{ $fieldPrefix }}setting-{{ $templateKey }}-{{ $key }}">{{ $field['label'] }}</label>
                            <div class="admin-range">
                                <input class="admin-range__input" type="range"
                                       id="{{ $fieldPrefix }}setting-{{ $templateKey }}-{{ $key }}"
                                       name="settings[{{ $key }}]" min="{{ $rangeMin }}" max="{{ $rangeMax }}"
                                        step="{{ $field['step'] ?? 1 }}" x-model="rangeValue"
                                        :disabled="tpl !== '{{ $templateKey }}'">
                                <output class="admin-range__value" x-text="rangeValue + '%'">{{ $rangeValue }}%</output>
                            </div>
                            @if (! empty($field['hint']))<p class="hint">{{ $field['hint'] }}</p>@endif
                            @error('settings.'.$key)<p class="aerror">{{ $message }}</p>@enderror
                        </div>
                    @else
                        <div class="afield">
                            <label for="{{ $fieldPrefix }}setting-{{ $templateKey }}-{{ $key }}">{{ $field['label'] }}</label>
                            @if ($field['type'] === 'textarea')
                                <textarea class="atextarea" id="{{ $fieldPrefix }}setting-{{ $templateKey }}-{{ $key }}" name="settings[{{ $key }}]"
                                          rows="5" :disabled="tpl !== '{{ $templateKey }}'">{{ $value }}</textarea>
                            @elseif ($field['type'] === 'select')
                                <select class="ainput" id="{{ $fieldPrefix }}setting-{{ $templateKey }}-{{ $key }}" name="settings[{{ $key }}]"
                                        :disabled="tpl !== '{{ $templateKey }}'">
                                    @foreach ($field['options'] as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input class="ainput" type="text" id="{{ $fieldPrefix }}setting-{{ $templateKey }}-{{ $key }}" name="settings[{{ $key }}]"
                                       value="{{ $value }}" :disabled="tpl !== '{{ $templateKey }}'">
                            @endif
                            @if (! empty($field['hint']))<p class="hint">{{ $field['hint'] }}</p>@endif
                            @error('settings.'.$key)<p class="aerror">{{ $message }}</p>@enderror
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    @endforeach

    <details style="margin-top:1rem">
        <summary style="cursor:pointer;font-weight:600">Change this part's layout</summary>
        <p class="hint" style="margin:.6rem 0">Choose a layout. The current choice is {{ App\Models\Section::TEMPLATE_OPTIONS[$section->template_key] ?? $section->template_key }}.</p>
        <div class="template-grid">
            @foreach (App\Models\Section::TEMPLATE_OPTIONS as $key => $label)
                <div class="template-opt">
                    <input type="radio" name="template_key" id="{{ $fieldPrefix }}tpl-{{ $key }}" value="{{ $key }}" x-model="tpl"
                           @checked(old('template_key', $section->template_key) === $key)>
                    <label for="{{ $fieldPrefix }}tpl-{{ $key }}">{{ $label }}</label>
                </div>
            @endforeach
        </div>
        @error('template_key')<p class="aerror">{{ $message }}</p>@enderror
        <div class="callout template-help" x-show="tpl" style="margin-top:.9rem" x-text="help[tpl] || ''"></div>
    </details>
</fieldset>

<details style="margin-top:1rem">
    <summary style="cursor:pointer;font-weight:600">Advanced options</summary>
<div class="form-grid">
    <div class="afield">
        <label for="{{ $fieldPrefix }}slug">Web address</label>
        <input class="ainput" type="text" id="{{ $fieldPrefix }}slug" name="slug" value="{{ old('slug', $section->slug) }}" placeholder="our-services">
        <p class="hint">Optional. Mostly used for internal links and search engines.</p>
        @error('slug')<p class="aerror">{{ $message }}</p>@enderror
    </div>

    <div class="afield">
        <label for="{{ $fieldPrefix }}anchor_id">Anchor</label>
        <input class="ainput" type="text" id="{{ $fieldPrefix }}anchor_id" name="anchor_id" value="{{ old('anchor_id', $section->anchor_id) }}" placeholder="services">
        <p class="hint">Optional. Lets other pages link straight to this section, e.g. <span class="mono">/#services</span>.</p>
        @error('anchor_id')<p class="aerror">{{ $message }}</p>@enderror
    </div>
</div>
<div class="afield">
    <label for="{{ $fieldPrefix }}sort_order">Order on page</label>
    <input class="ainput" type="number" id="{{ $fieldPrefix }}sort_order" name="sort_order" min="0" max="9999"
           value="{{ old('sort_order', $section->sort_order ?? 0) }}" style="max-width:120px">
    <p class="hint">Sections appear top to bottom in this order. Lower numbers first.</p>
    @error('sort_order')<p class="aerror">{{ $message }}</p>@enderror
</div>

<div class="check">
    <input type="checkbox" id="{{ $fieldPrefix }}is_visible" name="is_visible" value="1" @checked(old('is_visible', $section->is_visible ?? true))>
    <label for="{{ $fieldPrefix }}is_visible">
    Show this part on the page
        <span class="hint">Uncheck to keep the content but hide it from visitors.</span>
    </label>
</div>
</details>
