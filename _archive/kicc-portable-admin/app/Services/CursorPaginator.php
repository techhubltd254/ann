<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Cursor;

class CursorPaginator
{
    /**
     * Cursor-based pagination for large tables.
     * Use this instead of offset-based pagination for tables with >10K rows.
     */
    public static function paginate(Builder $query, int $perPage = 25, ?string $cursor = null, string $cursorColumn = 'id'): array
    {
        if ($cursor) {
            $decoded = json_decode(base64_decode($cursor), true);
            if ($decoded && isset($decoded[$cursorColumn])) {
                $query->where($cursorColumn, '>', $decoded[$cursorColumn]);
            }
        }

        $items = $query->orderBy($cursorColumn)->take($perPage + 1)->get();
        $hasMore = $items->count() > $perPage;

        $nextCursor = null;
        if ($hasMore) {
            $lastItem = $items->pop();
            $nextCursor = base64_encode(json_encode([$cursorColumn => $lastItem->{$cursorColumn}]));
        }

        return [
            'data' => $items->values()->toArray(),
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
            'per_page' => $perPage,
        ];
    }
}
