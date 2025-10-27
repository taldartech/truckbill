<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Auth;

trait TracksUser
{
    /**
     * Boot the trait and add model event listeners.
     */
    public static function bootTracksUser()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                // Only set created_by if not already set manually
                if (empty($model->created_by)) {
                    $model->created_by = Auth::id();
                }
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::saving(function ($model) {
            if (Auth::check()) {
                // In case updating() is not triggered (some mass operations)
                $model->updated_by = Auth::id();
            }
        });
    }

    /**
     * Optional relationships for convenience.
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
