<?php

namespace App\Models\Concerns;

use App\Models\Scopes\VillaScope;
use App\Models\Villa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Scopes a model to the authenticated user's villa and stamps new
 * records with the current villa_id, guaranteeing SaaS data isolation.
 */
trait BelongsToVilla
{
    public static function bootBelongsToVilla(): void
    {
        static::addGlobalScope(new VillaScope);

        static::creating(function ($model) {
            if (! $model->villa_id && Auth::check()) {
                $model->villa_id = Auth::user()->villa_id;
            }
        });
    }

    public function villa(): BelongsTo
    {
        return $this->belongsTo(Villa::class);
    }
}
