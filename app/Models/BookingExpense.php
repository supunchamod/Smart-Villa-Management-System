<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A cost the villa bears for one specific booking (not billed to the guest).
 */
class BookingExpense extends Model
{
    /**
     * Quick-pick categories offered in the UI. Free text is also accepted.
     */
    public const CATEGORIES = ['External Meals', 'Transport', 'Supplies', 'Staff / Guide', 'Activities', 'Other'];

    protected $fillable = [
        'booking_id',
        'category',
        'description',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
