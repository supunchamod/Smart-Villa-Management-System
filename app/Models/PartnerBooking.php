<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The external resort / hotel / cabana arrangement behind a Full Package
 * booking, and what the villa owes / has paid it.
 */
class PartnerBooking extends Model
{
    public const PENDING_ADVANCE = 'pending_advance';

    public const ADVANCE_PAID = 'advance_paid';

    public const SETTLED = 'settled';

    public const ADVANCE_CATEGORY = 'Partner Resort - Advance';

    public const FINAL_CATEGORY = 'Partner Resort - Final Settlement';

    protected $fillable = [
        'booking_id',
        'partner_name',
        'agreed_cost',
        'advance_paid',
        'advance_paid_on',
        'final_paid',
        'settled_on',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'agreed_cost' => 'decimal:2',
            'advance_paid' => 'decimal:2',
            'final_paid' => 'decimal:2',
            'advance_paid_on' => 'date',
            'settled_on' => 'date',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * The direct expenses this arrangement has created on the booking.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * The partner / resort is the booking's Stay Hotel / Accommodation Name.
     * (partner_name is a copy kept for sorting; the booking is the source.)
     */
    protected function partnerLabel(): Attribute
    {
        return Attribute::get(fn () => $this->booking?->stay_hotel_name ?: $this->partner_name);
    }

    /**
     * What is still owed to the partner (agreed cost minus the advance), or
     * null while the total agreed cost has not been entered yet.
     */
    protected function remainingCost(): Attribute
    {
        return Attribute::get(fn () => $this->agreed_cost === null
            ? null
            : max(0.0, round((float) $this->agreed_cost - (float) $this->advance_paid, 2)));
    }

    /**
     * The stage shown to staff. "Pending Final Settlement" is the flag raised
     * once the guest has checked out while the partner is still unpaid in full.
     */
    protected function stage(): Attribute
    {
        return Attribute::get(function () {
            if ($this->status === self::SETTLED) {
                return 'settled';
            }

            if ($this->status === self::ADVANCE_PAID) {
                if ($this->booking?->status === 'checked_out') {
                    return 'pending_final';
                }

                // An advance was paid but the total agreed cost isn't known yet.
                return $this->agreed_cost === null ? 'advance_partial' : 'advance_paid';
            }

            return 'pending_advance';
        });
    }

    /**
     * @return array{label: string, class: string}
     */
    protected function stageMeta(): Attribute
    {
        return Attribute::get(fn () => [
            'pending_advance' => ['label' => 'Pending Advance', 'class' => 'pending'],
            'advance_partial' => ['label' => 'Pending Advance / Partial', 'class' => 'pending'],
            'advance_paid' => ['label' => 'Advance Paid / Confirmed', 'class' => 'new'],
            'pending_final' => ['label' => 'Pending Final Settlement', 'class' => 'stuck'],
            'settled' => ['label' => 'Fully Settled', 'class' => 'won'],
        ][$this->stage]);
    }

    public function scopePendingFinal(Builder $query): Builder
    {
        return $query->where('status', self::ADVANCE_PAID)
            ->whereHas('booking', fn (Builder $booking) => $booking->where('status', 'checked_out'));
    }

    /**
     * Entries that need the owner's action: no advance recorded yet, or the
     * guest has left and the partner is still to be paid.
     */
    public static function needsAttentionCount(): int
    {
        return static::query()->where('status', self::PENDING_ADVANCE)->count()
            + static::query()->pendingFinal()->count();
    }
}
