<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Rows with a `sort_order` column.
 * - A new row without a sort_order goes to the end of its group.
 * - Model::ordered() lists rows in that order.
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
}
