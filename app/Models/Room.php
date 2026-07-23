<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name_or_number',
        'type',
        'price_per_night',
        'pricing_tiers',
        'capacity',
        'photo_url',
        'photo_urls',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_per_night' => 'decimal:2',
            'pricing_tiers' => 'array',
            'photo_urls' => 'array',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * The nightly rate for a given guest count. Rooms with no pricing_tiers
     * set (the common case) just charge the flat price_per_night regardless
     * of guest count. A tiered room (e.g. {"2": 12500, "4": 15000, "6":
     * 20000, "8": 25000}) charges the rate of the smallest tier that still
     * fits the guest count; a guest count above every tier falls back to
     * the top tier's rate rather than being rejected here (the room's
     * overall capacity is enforced separately).
     */
    public function rateForGuests(int $guests): float
    {
        if (empty($this->pricing_tiers)) {
            return (float) $this->price_per_night;
        }

        $tiers = collect($this->pricing_tiers)
            ->mapWithKeys(fn ($rate, $maxPax) => [(int) $maxPax => (float) $rate])
            ->sortKeys();

        foreach ($tiers as $maxPax => $rate) {
            if ($guests <= $maxPax) {
                return $rate;
            }
        }

        return (float) $tiers->last();
    }

    /**
     * The "from" rate shown on the room card: the lowest tier's rate for a
     * tiered room, or the flat rate otherwise.
     */
    protected function startingRate(): Attribute
    {
        return Attribute::get(function () {
            if (empty($this->pricing_tiers)) {
                return (float) $this->price_per_night;
            }

            return (float) collect($this->pricing_tiers)->min();
        });
    }

    /**
     * The full photo list for the public page's slider: the single cover
     * photo_url first (if set), then any additional gallery photo_urls -
     * kept as two separate fields rather than folding photo_url into the
     * gallery array so every place that already reads photo_url directly
     * keeps working unchanged.
     *
     * @return list<string>
     */
    protected function photos(): Attribute
    {
        return Attribute::get(function () {
            $gallery = array_values(array_filter((array) ($this->photo_urls ?? [])));
            $all = $this->photo_url ? [$this->photo_url, ...$gallery] : $gallery;

            return array_values(array_unique($all));
        });
    }
}
