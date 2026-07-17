<?php

namespace App\Models;

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
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
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
}
