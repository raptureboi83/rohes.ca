@php
    $type = old('item_type', $item->item_type);
    $fieldPrefix = $fieldPrefix ?? '';
    $imagePath = old('image_path', $item->image_path);
@endphp

<div x-data="{ type: @js($type), imagePath: @js($imagePath) }">
    <div class="afield">
        <label for="{{ $fieldPrefix }}title">Title</label>
        <input class="ainput" type="text" id="{{ $fieldPrefix }}title" name="title" value="{{ old('title', $item->title) }}"
               placeholder="e.g. Septic system inspections">
        <p class="hint">The main line. Leave blank only for a plain paragraph.</p>
        @error('title')<p class="aerror">{{ $message }}</p>@enderror
    </div>

    <div class="afield">
        <label for="{{ $fieldPrefix }}subtitle">Short line below title (optional)</label>
        <input class="ainput" type="text" id="{{ $fieldPrefix }}subtitle" name="subtitle" value="{{ old('subtitle', $item->subtitle) }}"
               placeholder="e.g. Lakeland Township">
        <p class="hint">A smaller line under the title, such as a service area or a date.</p>
        @error('subtitle')<p class="aerror">{{ $message }}</p>@enderror
    </div>

    <div class="afield">
        <label for="{{ $fieldPrefix }}body">Description</label>
        <textarea class="atextarea" id="{{ $fieldPrefix }}body" name="body" rows="6"
                  placeholder="The main description. Plain text; the site handles spacing and styling.">{{ old('body', $item->body) }}</textarea>
        <p class="hint">Plain text is fine; blank lines become separate paragraphs. Rich-text sections allow sanitized HTML.</p>
        @error('body')<p class="aerror">{{ $message }}</p>@enderror
    </div>

    <div class="afield">
        <label for="{{ $fieldPrefix }}image_library">Choose an image</label>
        <select class="ainput" id="{{ $fieldPrefix }}image_library" x-model="imagePath">
            <option value="">No image</option>
            @if ($imagePath && ! $availableImages->contains(fn ($image) => $image->url === $imagePath))
                <option value="{{ $imagePath }}">Current image (not in the media library)</option>
            @endif
            @foreach ($availableImages as $image)
                <option value="{{ $image->url }}">{{ $image->title ?: $image->original_name }}{{ $image->width ? ' ('.$image->width.' x '.$image->height.')' : '' }}</option>
            @endforeach
        </select>
        <p class="hint">Upload and preview files in the <a href="{{ route('admin.media.index') }}" target="_blank" rel="noopener">media library</a>, then choose one here.</p>
    </div>

    <div class="form-grid">
        <div class="afield">
            <label for="{{ $fieldPrefix }}image_path">Image path</label>
            <input class="ainput" type="text" id="{{ $fieldPrefix }}image_path" name="image_path" value="{{ $imagePath }}" x-model="imagePath"
                   placeholder="/media/legacy/example.jpg">
            <p class="hint">The path is editable if you need to enter a custom image address.</p>
            @error('image_path')<p class="aerror">{{ $message }}</p>@enderror
        </div>
        <div class="afield">
            <label for="{{ $fieldPrefix }}image_alt">Image description</label>
            <input class="ainput" type="text" id="{{ $fieldPrefix }}image_alt" name="image_alt" value="{{ old('image_alt', $item->image_alt) }}"
                   placeholder="e.g. Excavator on a rural job site">
            <p class="hint">Describes the image for screen readers and search engines. Leave blank if decorative.</p>
            @error('image_alt')<p class="aerror">{{ $message }}</p>@enderror
        </div>
    </div>

    @if ($imagePath)
        <div class="afield">
            <span class="hint">Current image</span>
            <img src="{{ media_src($imagePath) }}" alt="" style="max-height:120px;border-radius:6px;margin-top:.4rem">
        </div>
    @endif

    <div class="form-grid">
        <div class="afield">
            <label for="{{ $fieldPrefix }}link_url">Link address</label>
            <input class="ainput" type="text" id="{{ $fieldPrefix }}link_url" name="link_url" value="{{ old('link_url', $item->link_url) }}"
                   placeholder="https://…  or  /about">
            <p class="hint">Makes this item clickable. Use a full address for outside sites, or a path like <span class="mono">/contact</span> for this site.</p>
            @error('link_url')<p class="aerror">{{ $message }}</p>@enderror
        </div>

        <div class="afield">
            <label for="{{ $fieldPrefix }}link_text">Link wording</label>
            <input class="ainput" type="text" id="{{ $fieldPrefix }}link_text" name="link_text" value="{{ old('link_text', $item->link_text) }}"
                   placeholder="e.g. Read more">
            <p class="hint">The words shown on the link. Leave blank to use the title.</p>
            @error('link_text')<p class="aerror">{{ $message }}</p>@enderror
        </div>
    </div>

    @foreach (App\Models\SectionItem::DATA_FIELDS as $dataType => $fields)
        <div x-show="type === '{{ $dataType }}'" x-cloak style="margin-top:1rem">
            <h3 style="font-size:1rem;margin-bottom:.75rem">{{ App\Models\SectionItem::ITEM_TYPES[$dataType] }} details</h3>
            @foreach ($fields as $key => $field)
                @php $value = old('data.'.$key, $item->meta($key, $field['type'] === 'checkbox' ? false : '')); @endphp
                @if ($field['type'] === 'checkbox')
                    <div class="check">
                        <input type="hidden" name="data[{{ $key }}]" value="0" :disabled="type !== '{{ $dataType }}'">
                        <input type="checkbox" id="{{ $fieldPrefix }}data-{{ $key }}" name="data[{{ $key }}]" value="1"
                               :disabled="type !== '{{ $dataType }}'" @checked((bool) $value)>
                        <label for="{{ $fieldPrefix }}data-{{ $key }}">{{ $field['label'] }}
                            @if (! empty($field['hint']))<span class="hint">{{ $field['hint'] }}</span>@endif
                        </label>
                    </div>
                @else
                    <div class="afield">
                        <label for="{{ $fieldPrefix }}data-{{ $key }}">{{ $field['label'] }}</label>
                        <input class="ainput" type="text" id="{{ $fieldPrefix }}data-{{ $key }}" name="data[{{ $key }}]"
                               value="{{ $value }}" :disabled="type !== '{{ $dataType }}'">
                        @if (! empty($field['hint']))<p class="hint">{{ $field['hint'] }}</p>@endif
                        @error('data.'.$key)<p class="aerror">{{ $message }}</p>@enderror
                    </div>
                @endif
            @endforeach
        </div>
    @endforeach

    <details style="margin-top:1rem">
        <summary style="cursor:pointer;font-weight:600">More item options</summary>
        <div class="afield" style="margin-top:.75rem">
            <label for="{{ $fieldPrefix }}item_type">Display type</label>
            <select class="ainput" id="{{ $fieldPrefix }}item_type" name="item_type" x-model="type">
                @foreach (App\Models\SectionItem::ITEM_TYPES as $key => $label)
                    <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="hint">Usually set automatically from the section. Change this only if an item displays incorrectly.</p>
            @error('item_type')<p class="aerror">{{ $message }}</p>@enderror
        </div>

        <div class="afield">
            <label for="{{ $fieldPrefix }}sort_order">Order</label>
            <input class="ainput" type="number" id="{{ $fieldPrefix }}sort_order" name="sort_order" min="0" max="9999"
                   value="{{ old('sort_order', $item->sort_order ?? 0) }}" style="max-width:120px">
            <p class="hint">You can also reorder items with the up and down arrows on the list screen.</p>
            @error('sort_order')<p class="aerror">{{ $message }}</p>@enderror
        </div>
    </details>

</div>
