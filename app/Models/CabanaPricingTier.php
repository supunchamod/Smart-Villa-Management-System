<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CabanaPricingTier extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'cabana_type_id',
        'min_pax',
        'max_pax',
        'cabana_only_price',
        'half_board_price',
        'full_board_price',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cabana_only_price' => 'decimal:2',
            'half_board_price' => 'decimal:2',
            'full_board_price' => 'decimal:2',
        ];
    }

    public function cabanaType(): BelongsTo
    {
        return $this->belongsTo(CabanaType::class);
    }

    /**
     * The price column for a given board type, or null if that board
     * type isn't offered for this tier.
     */
    public function priceFor(string $boardType): ?float
    {
        $column = "{$boardType}_price";

        return $this->{$column} !== null ? (float) $this->{$column} : null;
    }
}
