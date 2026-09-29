<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'disk',
    'folder',
    'filename',
    'original_name',
    'mime_type',
    'size',
    'width',
    'height',
    'alt_text',
    'title',
])]
class Media extends Model
{
    /** @use HasFactory<\Database\Factories\MediaFactory> */
    use HasFactory;

    public const IMAGE_MIMES = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /**
     * Files live on the dedicated `media` disk, whose root is
     * public_path('media'). Folder is stored relative to that root, so the
     * public path is always prefixed rather than derived from the disk
     * name — this keeps working if the disk is ever renamed.
     */
    public function getUrlAttribute(): string
    {
        $path = trim($this->folder.'/'.$this->filename, '/');

        return $this->folder === 'root'
            ? '/media/'.$this->filename
            : '/media/'.$path;
    }

    /** Path relative to the media root, for filesystem operations. */
    public function getRelativePathAttribute(): string
    {
        return $this->folder === 'root'
            ? $this->filename
            : $this->folder.'/'.$this->filename;
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size;

        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024) {
                return round($bytes, $bytes < 10 && $unit !== 'B' ? 1 : 0).' '.$unit;
            }
            $bytes /= 1024;
        }

        return round($bytes, 1).' TB';
    }

    public function scopeImages(Builder $query): Builder
    {
        return $query->whereIn('mime_type', [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        ]);
    }

    public function scopeInFolder(Builder $query, ?string $folder): Builder
    {
        return $query->where('folder', $folder ?: 'root');
    }
}
