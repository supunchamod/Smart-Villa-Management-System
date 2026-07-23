<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LandingPageMenu extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'meal_type',
        'item_name',
        'description',
    ];

    public function scopeMealType(Builder $query, string $mealType): Builder
    {
        return $query->where('meal_type', $mealType);
    }
}
