<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait ManagesContent
{
    /**
     * Build a slug that is unique within the given table/column pair.
     *
     * The client types "Our Services"; the column is unique; a naive
     * `str_slug()` would throw a constraint violation and lose their
     * work. This appends -2, -3, ... instead.
     */
    protected function uniqueSlug(
        string $title,
        string $table,
        string $column = 'slug',
        ?int $ignoreId = null
    ): string {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'item';
        }

        $slug = $base;
        $suffix = 2;

        while ($this->slugExists($table, $column, $slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    protected function slugExists(string $table, string $column, string $slug, ?int $ignoreId): bool
    {
        $query = DB::table($table)
            ->where($column, $slug);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Slug for a section, which is unique per page rather than globally.
     */
    protected function uniqueSectionSlug(int $pageId, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'section';
        $slug = $base;
        $suffix = 2;

        while (DB::table('sections')
            ->where('page_id', $pageId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * Next manual sort order, so a new row lands at the bottom of the list
     * without the client having to invent a number.
     */
    protected function nextSortOrder(string $table, ?string $parentColumn = null, ?int $parentId = null): int
    {
        $query = DB::table($table);

        if ($parentColumn !== null) {
            $query->where($parentColumn, $parentId);
        }

        return ((int) $query->max('sort_order')) + 1;
    }

    /**
     * Turn an empty string into null so optional columns stay null rather
     than storing a meaningless ''.
     */
    protected function blankToNull(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return ($value === '' || $value === null) ? null : $value;
    }
}
