<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InvalidatesContentCache;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\SectionItem;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MediaController extends Controller
{
    use InvalidatesContentCache;

    /** Where new uploads land. Existing legacy/ and docs/ folders stay readable. */
    public const UPLOAD_FOLDER = 'uploads';

    public const MAX_UPLOAD_KB = 8192; // 8 MB

    /**
     * Extensions we are willing to store. This list, not the browser, decides
     * the extension of the stored file - see store().
     */
    public const ALLOWED = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
    ];

    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'all');
        $search = trim((string) $request->query('q', ''));

        $query = Media::query()->latest();

        $query->when($filter === 'images', fn ($q) => $q->images());
        $query->when($filter === 'files', fn ($q) => $q->where('mime_type', '!=', 'image/jpeg')
            ->where('mime_type', '!=', 'image/png')
            ->where('mime_type', '!=', 'image/webp')
            ->where('mime_type', '!=', 'image/gif'));

        if ($search !== '') {
            $needle = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
            $query->where(fn ($q) => $q->where('filename', 'like', $needle)
                ->orWhere('original_name', 'like', $needle)
                ->orWhere('alt_text', 'like', $needle)
                ->orWhere('title', 'like', $needle));
        }

        $media = $query->paginate(24)->withQueryString();

        // Deleting a file that a page still points at would leave a broken
        // image on the live site, so the index shows usage up front.
        $media->getCollection()->each(function (Media $item) {
            $item->setAttribute('usage', $this->usageFor($item));
        });

        return view('admin.media.index', [
            'media' => $media,
            'filter' => $filter,
            'search' => $search,
            'filters' => ['all' => 'All files', 'images' => 'Images', 'files' => 'Documents'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => [
                'required',
                'file',
                'max:'.self::MAX_UPLOAD_KB,
                // mimes: inspects the real file contents, so a renamed script
                // is rejected here rather than trusted from the extension.
                'mimes:'.implode(',', array_keys(self::ALLOWED)),
            ],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ], [
            'files.*.mimes' => 'That file type is not allowed. Use JPG, PNG, WebP, GIF or PDF.',
            'files.*.max' => 'That file is too large. The limit is 8 MB per file.',
        ]);

        // The form posts a multi-file input as files[][], so the individual
        // entries arrive as a numerically indexed array. Collect them here so
        // the loop below does not depend on the keys being sequential.
        $uploads = array_values(array_filter($data['files'], 'is_object'));

        Storage::disk('media')->makeDirectory(self::UPLOAD_FOLDER);

        $stored = 0;
        $skipped = [];

        foreach ($uploads as $upload) {
            $extension = strtolower($upload->getClientOriginalExtension());

            if (! isset(self::ALLOWED[$extension])) {
                $skipped[] = $upload->getClientOriginalName().' (unsupported type)';

                continue;
            }

            $filename = $this->uniqueFilename($upload, $extension);

            $path = self::UPLOAD_FOLDER.'/'.$filename;

            Storage::disk('media')->putFileAs(self::UPLOAD_FOLDER, $upload, $filename);

            // Optimize images for web: resize to max 1600px width, convert to WebP.
            // This keeps hero backgrounds and card images fast on desktop and mobile.
            if (str_starts_with(self::ALLOWED[$extension], 'image/')) {
                $optimized = $this->optimizeImage($path, $extension);
                if ($optimized !== null) {
                    $path = $optimized[0];
                    $extension = $optimized[1];
                }
            }

            $stored++;

            [$mime, $width, $height] = $this->inspectStoredFile($path, $extension);

            Media::create([
                'disk' => 'media',
                'folder' => self::UPLOAD_FOLDER,
                'filename' => basename($path),
                'original_name' => $this->clean($upload->getClientOriginalName()),
                'mime_type' => $mime,
                'size' => Storage::disk('media')->size($path) ?: 0,
                'width' => $width,
                'height' => $height,
                'alt_text' => $this->clean($data['alt_text'] ?? null),
                'title' => null,
            ]);
        }

        $this->forgetContentCache();

        if ($stored === 0) {
            return back()->with('error', 'Nothing was uploaded. Allowed types: JPG, PNG, WebP, GIF and PDF.');
        }

        $message = $stored.' file'.($stored === 1 ? '' : 's').' uploaded.';

        if ($skipped !== []) {
            $message .= ' Skipped: '.implode(', ', $skipped).'.';
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, Media $media): RedirectResponse
    {
        $data = $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $media->update([
            'alt_text' => $this->clean($data['alt_text'] ?? null),
            'title' => $this->clean($data['title'] ?? null),
        ]);

        $this->forgetContentCache();

        return back()->with('success', 'File details saved.');
    }

    public function destroy(Media $media): RedirectResponse
    {
        $usage = $this->usageFor($media);

        if ($usage !== []) {
            return back()->with(
                'error',
                'This file is still in use and was not deleted. '.
                'It is referenced by: '.implode('; ', $usage).'. Remove those references first.'
            );
        }

        Storage::disk($media->disk)->delete($media->relative_path);

        $media->delete();

        $this->forgetContentCache();

        return back()->with('success', 'File deleted.');
    }

    /**
     * Every place this file is referenced, described in words a site owner can
     * act on. Returns [] when the file is safe to delete.
     *
     * @return list<string>
     */
    protected function usageFor(Media $media): array
    {
        $url = $media->url;
        $usage = [];

        $items = SectionItem::query()
            ->where('image_path', $url)
            ->orWhere('data', 'like', '%'.$url.'%')
            ->with('section')
            ->get();

        foreach ($items as $item) {
            $page = $item->section?->page?->title ?? 'a page';
            $usage[] = 'item "'.($item->title ?: $item->id).'" on '.$page;
        }

        $settings = Setting::query()
            ->where('value', 'like', '%'.$url.'%')
            ->pluck('key');

        foreach ($settings as $key) {
            $usage[] = 'site setting '.$key;
        }

        return array_values(array_unique($usage));
    }

    /**
     * Build a safe stored filename. The client's name is only used to form a
     * readable slug, never as the stored path, so traversal ("../"), odd
     * characters and double extensions ("x.php.jpg") cannot survive.
     *
     * Accepts either UploadedFile class: Laravel's and Symfony's are unrelated
     * by inheritance, and a type hint would reject one of them. Only methods
     * both provide are used.
     */
    protected function uniqueFilename(object $upload, string $extension): string
    {
        $base = Str::slug(pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME));
        $base = $base !== '' ? Str::limit($base, 60, '') : 'file';

        $filename = $base.'-'.substr(bin2hex(random_bytes(4)), 0, 8).'.'.$extension;
        $counter = 1;

        while (Media::query()->where('folder', self::UPLOAD_FOLDER)->where('filename', $filename)->exists()) {
            $filename = $base.'-'.$counter.'-'.substr(bin2hex(random_bytes(4)), 0, 8).'.'.$extension;
            $counter++;
        }

        return $filename;
    }

    /**
     * Read the true type and pixel dimensions back off the stored file, so the
     * database reflects what actually landed on disk rather than what the
     * browser claimed. Non-images get null dimensions.
     *
     * @return array{0: string, 1: ?int, 2: ?int}
     */
    protected function inspectStoredFile(string $relativePath, string $fallbackExtension): array
    {
        $path = Storage::disk('media')->path($relativePath);

        if (is_file($path) && function_exists('getimagesize')) {
            $info = @getimagesize($path);

            if (is_array($info) && ! empty($info['mime'])) {
                return [$info['mime'], $info[0] ?? null, $info[1] ?? null];
            }
        }

        return [self::ALLOWED[$fallbackExtension], null, null];
    }

    /**
     * Optimize an uploaded image for web delivery.
     *
     * Resizes to a maximum width of 1600px (preserving aspect ratio) and
     * converts to WebP at quality 82. This keeps hero backgrounds and card
     * images fast on both desktop and mobile. Transparency is preserved for
     * PNG and WebP sources.
     *
     * @return array{0: string, 1: string}|null [stored path relative to media root, new extension]
     */
    protected function optimizeImage(string $relativePath, string $originalExtension): ?array
    {
        $disk = Storage::disk('media');
        $fullPath = $disk->path($relativePath);

        if (! is_file($fullPath) || ! function_exists('exif_imagetype')) {
            return null;
        }

        $type = exif_imagetype($fullPath);

        $createFunc = match ($type) {
            IMAGETYPE_JPEG => 'imagecreatefromjpeg',
            IMAGETYPE_PNG => 'imagecreatefrompng',
            IMAGETYPE_GIF => 'imagecreatefromgif',
            IMAGETYPE_WEBP => 'imagecreatefromwebp',
            default => null,
        };

        if (! $createFunc) {
            return null;
        }

        $source = $createFunc($fullPath);
        if (! $source) {
            return null;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        $maxWidth = 1600;

        if ($sourceWidth > $maxWidth) {
            $ratio = $maxWidth / $sourceWidth;
            $newWidth = $maxWidth;
            $newHeight = max(1, (int) round($sourceHeight * $ratio));
        } else {
            $newWidth = $sourceWidth;
            $newHeight = $sourceHeight;
        }

        $canvas = imagecreatetruecolor($newWidth, $newHeight);

        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);
        }

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

        $webpFilename = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', basename($relativePath));
        $webpPath = self::UPLOAD_FOLDER.'/'.$webpFilename;

        imagewebp($canvas, $disk->path($webpPath), 82);

        imagedestroy($source);
        imagedestroy($canvas);

        // Remove the original unoptimized file.
        $disk->delete($relativePath);

        return [$webpPath, 'webp'];
    }

    /** Strip markup and collapse whitespace before storing any user text. */
    protected function clean(?string $value): ?string
    {
        $value = trim(strip_tags((string) $value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value === '' ? null : Str::limit($value, 255, '');
    }
}
