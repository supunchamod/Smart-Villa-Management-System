<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'room_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'check_in',
        'check_out',
        'total_amount',
        'advance_payment',
        'final_settlement_amount',
        'status',
        'checked_out_at',
        'payment_method',
        'board_type',
        'selected_menu_items',
        'guests_adults',
        'guests_children',
        'bbq_addon',
        'safari_jeep_addon',
        'outdoor_dining_preference',
        'reminder_sent_at',
        'checkin_info_sent_at',
        'review_request_sent_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'total_amount' => 'decimal:2',
            'advance_payment' => 'decimal:2',
            'final_settlement_amount' => 'decimal:2',
            'checked_out_at' => 'datetime',
            'selected_menu_items' => 'array',
            'bbq_addon' => 'boolean',
            'safari_jeep_addon' => 'boolean',
            'outdoor_dining_preference' => 'boolean',
            'reminder_sent_at' => 'datetime',
            'checkin_info_sent_at' => 'datetime',
            'review_request_sent_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Confirmed bookings with a check-in still ahead of today, soonest
     * first - the "what's coming up" query shared by the calendar's agenda
     * list and the Profit Analyzer's upcoming-bookings widget.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'confirmed')
            ->whereDate('check_in', '>=', now()->toDateString())
            ->orderBy('check_in');
    }

    /**
     * The outstanding balance. Always zero once checked out - the advance
     * and final settlement amounts are kept separate (rather than folding
     * the settlement into advance_payment) so the income ledger can still
     * report them as two distinct cash-in events.
     */
    protected function remainingBalance(): Attribute
    {
        return Attribute::get(function () {
            if ($this->status === 'checked_out') {
                return 0.0;
            }

            return (float) $this->total_amount - (float) $this->advance_payment;
        });
    }

    /**
     * Up to two initials derived from the customer's name, for avatar
     * circles where there's no profile photo to show instead.
     */
    protected function customerInitials(): Attribute
    {
        return Attribute::get(function () {
            $initials = collect(preg_split('/\s+/', trim((string) $this->customer_name)))
                ->filter()
                ->take(2)
                ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
                ->implode('');

            return $initials !== '' ? $initials : '?';
        });
    }

    /**
     * Advance payment as a percentage of the total booking amount.
     */
    protected function paymentProgress(): Attribute
    {
        return Attribute::get(function () {
            if ((float) $this->total_amount <= 0.0) {
                return 0.0;
            }

            return round(((float) $this->advance_payment / (float) $this->total_amount) * 100, 1);
        });
    }

    /**
     * Human-readable board type - "cabana_only" -> "Cabana Only", etc.
     * Null for bookings made before the meal-plan feature existed, or
     * created without one via the internal admin form.
     */
    protected function boardTypeLabel(): Attribute
    {
        return Attribute::get(fn () => $this->board_type
            ? str(str_replace('_', ' ', $this->board_type))->title()->toString()
            : null);
    }

    /**
     * A simple, guest-facing payment status independent of the booking's
     * lifecycle status (a still-"confirmed" booking can already be fully
     * paid ahead of arrival, for example).
     */
    protected function paymentStatusLabel(): Attribute
    {
        return Attribute::get(function () {
            if ($this->remaining_balance <= 0.0) {
                return 'Fully Paid';
            }

            return (float) $this->advance_payment > 0.0 ? 'Partially Paid' : 'Unpaid';
        });
    }
}
