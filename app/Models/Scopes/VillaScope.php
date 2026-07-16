<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class VillaScope implements Scope
{
    /**
     * Constrain the query to the authenticated user's villa.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check() && Auth::user()->villa_id) {
            $builder->where($model->qualifyColumn('villa_id'), Auth::user()->villa_id);
        }
    }
}
