<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait TracksUserActions
{
    /**
     * Boot the trait and set up event listeners.
     */
    public static function bootTracksUserActions()
    {
        // When a model is being created
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
            }
        });

        // When a model is being updated
        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        // When a model is being soft-deleted (if using soft deletes)
        static::deleting(function ($model) {
            if (Auth::check() && method_exists($model, 'trashed') && !$model->isForceDeleting()) {
                $model->deleted_by = Auth::id();
                // Save the model to persist the deleted_by field
                $model->saveQuietly(); // Use saveQuietly to prevent recursive events
            }
        });

        // For force deletion (permanent delete)
//        static::forceDeleting(function ($model) {
//            // Optionally handle force deletion if needed
//            // For example, you might want to clear user tracking fields
//            // when permanently deleting records
//        });
    }

    /**
     * Get the user who created the model.
     */
    public function creator()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    /**
     * Get the user who last updated the model.
     */
    public function editor()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by');
    }

    /**
     * Get the user who deleted the model.
     */
    public function deleter()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'deleted_by');
    }
}
