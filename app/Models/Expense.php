<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Every cost the villa records. booking_id is NULL for general operating
 * expenses (utilities, salaries...) and set for a direct cost tied to one
 * specific booking (external meals, transport...), which then reduces that
 * booking's net profit.
 */
class Expense extends Model
{
    use HasFactory;

    /**
     * Quick-pick categories for general operating expenses.
     */
    public const GENERAL_CATEGORIES = ['Utilities', 'Maintenance', 'Salaries', 'Supplies', 'Housekeeping', 'Laundry', 'Food & Beverage', 'Marketing'];

    /**
     * Quick-pick categories for guest-related direct costs.
     */
    public const BOOKING_CATEGORIES = ['External Meals', 'Transport', 'Supplies', 'Staff / Guide', 'Activities', 'Other'];

    protected $fillable = [
        'booking_id',
        'category',
        'amount',
        'description',
        'expense_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * General operating expenses (not linked to a booking).
     */
    public function scopeGeneral(Builder $query): Builder
    {
        return $query->whereNull('booking_id');
    }

    /**
     * Direct costs linked to a specific booking.
     */
    public function scopeBookingLinked(Builder $query): Builder
    {
        return $query->whereNotNull('booking_id');
    }

    /**
     * Plain array for the JSON replies the booking-side cost lists use.
     *
     * @return array<string, mixed>
     */
    public function toListItem(): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'date' => $this->expense_date->format('d M Y'),
            'url' => route('bookings.expenses.destroy', $this),
        ];
    }
}
