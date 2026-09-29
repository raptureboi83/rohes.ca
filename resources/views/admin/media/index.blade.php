@extends('admin.layout')

@section('title', 'Media library')

@section('content')
<div class="admin-head">
    <div>
        <h1>Media library</h1>
        <p>{{ $media->total() }} file{{ $media->total() === 1 ? '' : 's' }}. Copy a path into an image field, or upload something new.</p>
    </div>
</div>

@if (session('success'))
    <div class="flash flash--success" role="status">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="flash flash--error" role="alert">{{ session('error') }}</div>
@endif

<div class="admin-card" style="margin-bottom:1.25rem">
    <div class="admin-card__body">
        <h2 style="font-size:1rem;margin:0 0 .75rem">Upload files</h2>

        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="field">
                <label for="files">Choose files</label>
                <input class="ainput" type="file" id="files" name="files[]" multiple required
                       accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,image/jpeg,image/png,image/webp,image/gif,application/pdf">
                <p class="hint">JPG, PNG, WebP, GIF or PDF. Up to 8&nbsp;MB each, 20 files at a time.</p>
                @error('files')<p class="aerror">{{ $message }}</p>@enderror
                @error('files.*')<p class="aerror">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="alt_text">Image description (optional, applied to all of them)</label>
                <input class="ainput" type="text" id="alt_text" name="alt_text" maxlength="255"
                       value="{{ old('alt_text') }}" placeholder="Describes the image for screen readers">
                <p class="hint">Leave blank for decorative images. You can set a different description on each file afterwards.</p>
            </div>

            <div class="form-actions">
                <button type="submit" class="abtn abtn--primary">Upload</button>
            </div>
        </form>
    </div>
</div>

<div class="admin-toolbar">
    <div class="admin-filters">
        @foreach ($filters as $key => $label)
            <a href="{{ route('admin.media.index', array_filter(['filter' => $key, 'q' => $search])) }}"
               class="admin-filter {{ $filter === $key ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.media.index') }}" class="admin-search">
        @if ($filter !== 'all')
            <input type="hidden" name="filter" value="{{ $filter }}">
        @endif
        <input class="ainput" type="search" name="q" value="{{ $search }}"
               placeholder="Search by file or description" aria-label="Search media">
        <button type="submit" class="abtn abtn--ghost">Search</button>
        @if ($search !== '')
            <a href="{{ route('admin.media.index') }}" class="abtn abtn--ghost">Clear</a>
        @endif
    </form>
</div>

@if ($media->isEmpty())
    <div class="admin-card">
        <div class="admin-card__body">
            <p class="hint">
                @if ($search !== '')
                    Nothing matches "{{ $search }}".
                @else
                    No files here yet. Upload something above.
                @endif
            </p>
        </div>
    </div>
@else
    <div class="media-grid">
        @foreach ($media as $item)
            <figure class="media-card">
                <div class="media-card__preview">
                    @if ($item->is_image)
                        <img src="{{ $item->url }}" alt="{{ $item->alt_text ?? '' }}" loading="lazy">
                    @else
                        <span class="media-card__ext">{{ strtoupper(pathinfo($item->filename, PATHINFO_EXTENSION)) }}</span>
                    @endif
                </div>

                <figcaption class="media-card__body">
                    <strong class="media-card__name" title="{{ $item->filename }}">{{ $item->original_name }}</strong>
                    <span class="media-card__meta">
                        {{ $item->human_size }}
                        @if ($item->width) &middot; {{ $item->width }}&times;{{ $item->height }} @endif
                    </span>

                    <code class="media-card__path">{{ $item->url }}</code>

                    @if ($item->usage)
                        <span class="media-card__use" title="{{ implode('; ', $item->usage) }}">
                            In use in {{ count($item->usage) }} place{{ count($item->usage) === 1 ? '' : 's' }}
                        </span>
                    @else
                        <span class="media-card__use media-card__use--free">Not in use</span>
                    @endif

                    <details class="media-card__edit">
                        <summary>Edit details</summary>
                        <form method="POST" action="{{ route('admin.media.update', $item) }}" style="margin-top:.6rem">
                            @csrf
                            @method('PUT')
                            <div class="field">
                                <label for="title-{{ $item->id }}">Title</label>
                                <input class="ainput" type="text" id="title-{{ $item->id }}" name="title" maxlength="255"
                                       value="{{ $item->title }}">
                            </div>
                            <div class="field">
                                <label for="alt-{{ $item->id }}">Image description</label>
                                <input class="ainput" type="text" id="alt-{{ $item->id }}" name="alt_text" maxlength="255"
                                       value="{{ $item->alt_text }}">
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="abtn abtn--primary abtn--sm">Save</button>
                            </div>
                        </form>
                    </details>

                    <form method="POST" action="{{ route('admin.media.destroy', $item) }}"
                          onsubmit="return confirm(@js($item->usage
                              ? 'This file is used on the website and will not be deleted. Delete anyway?'
                              : 'Delete this file permanently?'));">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="abtn abtn--danger abtn--sm">Delete</button>
                    </form>
                </figcaption>
            </figure>
        @endforeach
    </div>

    <div class="admin-pager">
        {{ $media->links() }}
    </div>
@endif
@endsection
