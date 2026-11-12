<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Rows with a `sort_order` column.
 * - A new row without a sort_order goes to the end of its group.
 * - Model::ordered() lists rows in that order.
 * - $row->move('up' | 'down') swaps it with its neighbour.
 * Override sortGroupColumn() to number rows inside a parent (e.g. lessons per course).
 */
trait HasSortOrder
{
    public static function bootHasSortOrder(): void
    {
        static::creating(function ($model) {
            if ($model->sort_order === null) {
                $model->sort_order = $model->sortSiblings()->max('sort_order') + 1;
            }
        });
    }

    /** Parent column that groups the rows, or null for one list. */
    public function sortGroupColumn(): ?string
    {
        return null;
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** Rows in the same group as this one. */
    public function sortSiblings(): Builder
    {
        $column = $this->sortGroupColumn();

        return static::query()->when($column, fn ($q) => $q->where($column, $this->{$column}));
    }

    /** Swap this row with the one before ('up') or after ('down') it. Renumbers the group 1, 2, 3 … */
    public function move(string $direction): void
    {
        DB::transaction(function () use ($direction) {
            $ids = $this->sortSiblings()->ordered()->lockForUpdate()->pluck('id')->all();
            $from = array_search($this->getKey(), $ids);
            $to = $direction === 'up' ? $from - 1 : $from + 1;

            if ($from === false || ! isset($ids[$to])) {
                return;
            }

            [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

            foreach ($ids as $position => $id) {
                static::whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });
    }
}
