@php $editing = isset($page) && $page->exists; @endphp

<div class="afield">
    <label for="title">Page title <span aria-hidden="true">*</span></label>
    <input class="ainput" type="text" id="title" name="title" value="{{ old('title', $page->title) }}" required
           placeholder="e.g. Services">
    <p class="hint">This is the heading visitors see. It does not have to match the web address.</p>
    @error('title')<p class="aerror">{{ $message }}</p>@enderror
</div>

<div class="form-grid">
    <div class="afield">
        <label for="nav_label">Menu label</label>
        <input class="ainput" type="text" id="nav_label" name="nav_label" value="{{ old('nav_label', $page->nav_label) }}"
               placeholder="Leave blank to use the page title">
        <p class="hint">If you want a shorter word in the navigation bar than the full page title, type it here.</p>
        @error('nav_label')<p class="aerror">{{ $message }}</p>@enderror
    </div>

    <div class="afield">
        <label for="slug">Web address</label>
        <input class="ainput" type="text" id="slug" name="slug" value="{{ old('slug', $page->slug) }}" placeholder="services">
        <p class="hint">Lowercase letters, numbers and dashes only. Leave blank and we will create it from the title.</p>
        @error('slug')<p class="aerror">{{ $message }}</p>@enderror
    </div>
</div>

<div class="form-grid">
    <div class="afield">
        <label for="meta_title">Search engine title</label>
        <input class="ainput" type="text" id="meta_title" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}"
               placeholder="Leave blank to use the page title">
        <p class="hint">The blue line in Google. Roughly 60 characters works best.</p>
        @error('meta_title')<p class="aerror">{{ $message }}</p>@enderror
    </div>

    <div class="afield">
        <label for="meta_image">Social sharing image</label>
        @php $metaImage = old('meta_image', $page->meta_image); @endphp
        <div x-data="{ imagePath: @js($metaImage), imageError: false, previewUrl() { return this.imagePath ? new URL(this.imagePath, @js(url('/'))).toString() : '' } }">
            <input type="hidden" name="meta_image" value="{{ $metaImage }}" :value="imagePath">
            <select class="ainput" id="meta_image" x-model="imagePath">
                <option value="">No social image</option>
                @if ($metaImage && ! $availableImages->contains(fn ($image) => $image->url === $metaImage))
                    <option value="{{ $metaImage }}">Current image (not in the media library)</option>
                @endif
                @foreach ($availableImages as $image)
                    <option value="{{ $image->url }}">{{ $image->title ?: $image->original_name }}</option>
                @endforeach
            </select>
            <input class="ainput" type="text" style="margin-top:.5rem" aria-label="Custom social image path"
                   x-model="imagePath" placeholder="Or enter a custom image path">
                 @include('admin.partials.image-preview')
        </div>
        <p class="hint">Optional. Choose an uploaded image or enter a custom path. This is shown when the page is shared.</p>
        @error('meta_image')<p class="aerror">{{ $message }}</p>@enderror
    </div>
</div>

<div class="afield">
    <label for="meta_description">Search engine description</label>
    <textarea class="atextarea" id="meta_description" name="meta_description" rows="3"
              placeholder="A short summary of this page, about 150 characters.">{{ old('meta_description', $page->meta_description) }}</textarea>
    <p class="hint">The grey paragraph in Google. Leave blank to use the default site description.</p>
    @error('meta_description')<p class="aerror">{{ $message }}</p>@enderror
</div>

<div class="afield">
    <label for="sort_order">Order in list</label>
    <input class="ainput" type="number" id="sort_order" name="sort_order" min="0" max="9999"
           value="{{ old('sort_order', $page->sort_order ?? 0) }}" style="max-width:120px">
    <p class="hint">Lower numbers appear first.</p>
    @error('sort_order')<p class="aerror">{{ $message }}</p>@enderror
</div>

<h3 style="font-size:1rem;margin-top:1.5rem">Visibility</h3>

<div class="check">
    <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $page->is_active ?? true))>
    <label for="is_active">
        Show this page on the website
        <span class="hint">Uncheck to hide it. The page and its content are kept, so you can switch it back on at any time.</span>
    </label>
</div>

<div class="check">
    <input type="checkbox" id="show_in_nav" name="show_in_nav" value="1" @checked(old('show_in_nav', $page->show_in_nav ?? true))>
    <label for="show_in_nav">
        Include this page in the navigation menu
        <span class="hint">Uncheck if the page should only be reachable by its direct address.</span>
    </label>
</div>

@if ($editing && $page->is_home)
    <div class="callout">
        <b>This is the home page.</b> Its address and visibility cannot be changed, and it cannot be deleted.
    </div>
@endif
