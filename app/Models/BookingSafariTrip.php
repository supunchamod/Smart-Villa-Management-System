<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One scheduled safari tour of a booking (a booking can have several, on
 * different days).
 */
class BookingSafariTrip extends Model
{
    protected $fillable = [
        'booking_id',
        'safari_date',
        'tour_name',
        'pickup_time',
        'pax_count',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'safari_date' => 'date',
            'pax_count' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Pickup time as HH:MM (the column is a TIME, stored as HH:MM:SS).
     */
    protected function pickupTimeShort(): Attribute
    {
        return Attribute::get(fn () => $this->pickup_time ? substr((string) $this->pickup_time, 0, 5) : null);
    }

    /**
     * Plain array for the popups, bills and invoices.
     *
     * @return array<string, mixed>
     */
    public function toDisplay(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->safari_date->format('D, d M Y'),
            'date_short' => $this->safari_date->format('d M Y'),
            'date_iso' => $this->safari_date->toDateString(),
            'tour' => $this->tour_name,
            'pickup_time' => $this->pickup_time_short,
            'pax' => $this->pax_count,
            'notes' => $this->notes,
        ];
    }
}
