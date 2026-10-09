<?php

namespace App\Support;

use App\Models\MediaAsset;
use Illuminate\Support\Collection;

/**
 * Request-scoped index of media_assets rows.
 *
 * The native payload resolves several hundred tiles. Asking TiDB for each one
 * separately costs a full network round trip per tile (~35 ms on this box),
 * which is what made the cold build take 35 s. This index loads every row the
 * page needs for a given owner type in chunks of 400 ids, so the same work
 * costs a handful of queries.
 *
 * Only `status = ready` rows are loaded — exactly the filter every resolver
 * applies — and rows are kept in ascending id order so `last()` reproduces
 * `latest('id')->first()`.
 */
class MediaAssetIndex
{
    /** @var array<string, Collection> "type|id" => rows, id ascending */
    private array $rows = [];

    /** @var array<string, bool> */
    private array $primed = [];

    private const CHUNK = 400;

    /** Load every ready row for the given owners of one type. Safe to call twice. */
    public function prime(string $ownerType, array $ownerIds, bool $withDerivatives = true): void
    {
        $ownerIds = array_values(array_unique(array_filter(
            array_map('intval', $ownerIds),
            fn ($id) => $id > 0
        )));

        $missing = array_values(array_filter(
            $ownerIds,
            fn ($id) => ! isset($this->primed[$ownerType . '|' . $id])
        ));

        if (empty($missing)) {
            return;
        }

        foreach (array_chunk($missing, self::CHUNK) as $chunk) {
            $query = MediaAsset::query()
                ->where('owner_type', $ownerType)
                ->whereIn('owner_id', $chunk)
                ->where('status', 'ready')
                ->orderBy('id');

            if ($withDerivatives) {
                $query->with('derivatives');
            }

            foreach ($query->get() as $row) {
                $key = $ownerType . '|' . (int) $row->owner_id;
                if (! isset($this->rows[$key])) {
                    $this->rows[$key] = new Collection();
                }
                $this->rows[$key]->push($row);
            }

            foreach ($chunk as $id) {
                $this->primed[$ownerType . '|' . $id] = true;
            }
        }
    }

    public function isPrimed(string $ownerType, int $ownerId): bool
    {
        return isset($this->primed[$ownerType . '|' . $ownerId]);
    }

    /** All ready rows for one owner, id ascending. */
    public function forOwner(string $ownerType, int $ownerId): Collection
    {
        return $this->rows[$ownerType . '|' . $ownerId] ?? new Collection();
    }

    /** Newest ready row for a slot, or for any of several slots. */
    public function forSlot(string $ownerType, int $ownerId, string|array $slot): ?MediaAsset
    {
        $slots = is_array($slot) ? $slot : [$slot];

        return $this->forOwner($ownerType, $ownerId)
            ->filter(fn ($a) => in_array($a->slot, $slots, true))
            ->last();
    }

    /** Newest ready row of one kind. */
    public function forKind(string $ownerType, int $ownerId, string $kind): ?MediaAsset
    {
        return $this->forOwner($ownerType, $ownerId)
            ->filter(fn ($a) => $a->kind === $kind)
            ->last();
    }

    public function queryCount(): int
    {
        return count($this->primed);
    }
}
