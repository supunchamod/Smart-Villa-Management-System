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
        'expense_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    /**
     * The day the cost was incurred; falls back to when it was recorded.
     */
    protected function incurredOn(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(fn () => ($this->expense_date ?? $this->created_at)->copy());
    }

    /**
     * Plain array for the JSON replies the interactive cost lists use.
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
            'date' => $this->incurred_on->format('d M Y'),
            'url' => route('bookings.expenses.destroy', $this),
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
