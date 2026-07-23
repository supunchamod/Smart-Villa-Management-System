<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CabanaType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'room_id',
        'name',
        'image_url',
        'description',
        'max_capacity',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * The bookable inventory Room this cabana type is linked to. Room
     * stays the source of truth for status/availability and the
     * bookings.room_id foreign key; CabanaType layers public marketing
     * content and per-board-type tiered pricing on top of it. Null until
     * an admin links one, in which case this cabana type is a draft and
     * never shown on the public booking page (see PublicBookingController).
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function pricingTiers(): HasMany
    {
        return $this->hasMany(CabanaPricingTier::class)->orderBy('min_pax');
    }

    /**
     * The nightly rate for a given guest count and board type
     * (cabana_only|half_board|full_board). Each tier stores a complete
     * rate per board type (not a base rate plus a computed meal
     * supplement), so this simply finds the bracket the guest count
     * falls into and returns that board type's price - falling back to
     * the top bracket's rate if the guest count exceeds every configured
     * tier, and returning null if that board type has no price set for
     * the matched tier (e.g. Half Board not offered for this cabana).
     */
    public function rateForGuests(int $guests, string $boardType): ?float
    {
        $tiers = $this->pricingTiers;

        $tier = $tiers->first(fn (CabanaPricingTier $tier) => $guests >= $tier->min_pax && $guests <= $tier->max_pax)
            ?? $tiers->sortByDesc('max_pax')->first();

        return $tier?->priceFor($boardType);
    }

    /**
     * The lowest configured rate across every tier and board type, shown
     * as the "from" price on the public page's cabana card.
     */
    protected function startingRate(): Attribute
    {
        return Attribute::get(function () {
            $prices = $this->pricingTiers
                ->flatMap(fn (CabanaPricingTier $tier) => [
                    $tier->cabana_only_price,
                    $tier->half_board_price,
                    $tier->full_board_price,
                ])
                ->filter(fn ($price) => $price !== null)
                ->map(fn ($price) => (float) $price);

            return $prices->isNotEmpty() ? $prices->min() : null;
        });
    }
}
