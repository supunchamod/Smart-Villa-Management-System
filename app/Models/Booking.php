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
     * Board type -> display label for the internal admin "Add/Edit
     * Booking" form and everywhere else a booking's board type needs a
     * human-readable name. Deliberately the same value vocabulary as
     * PublicBookingController::BOARD_TYPE_LABELS (cabana_only/half_board/
     * full_board), so a booking's board_type means the same thing
     * regardless of whether staff or a guest created it - just with
     * fuller, abbreviation-suffixed labels suited to admin UI rather than
     * guest-facing copy. bed_and_breakfast is admin-only for now: the
     * public villa page's pricing tiers have no bed_and_breakfast_price
     * column yet, so it isn't offered there.
     */
    public const BOARD_TYPES = [
        'cabana_only' => 'Cabana Only (Room Only)',
        'bed_and_breakfast' => 'Bed & Breakfast (BB)',
        'half_board' => 'Half Board (HB)',
        'full_board' => 'Full Board (FB)',
    ];

    /**
     * Board type -> short label for the compact pill shown under the room
     * name (Manage Bookings table, mobile cards). BOARD_TYPES' labels are
     * sized for a dropdown ("Bed & Breakfast (BB)"), too wide for a badge.
     */
    private const BOARD_TYPE_BADGE_LABELS = [
        'cabana_only' => 'Cabana Only',
        'bed_and_breakfast' => 'B&B',
        'half_board' => 'HB',
        'full_board' => 'FB',
    ];

    /**
     * Board type -> the CSS badge modifier class used to color its pill
     * consistently everywhere it's shown (Manage Bookings table, quick
     * view modal). Kept separate from BOARD_TYPES so the labels above can
     * change without touching styling.
     */
    private const BOARD_TYPE_BADGE_CLASSES = [
        'cabana_only' => 'board-badge-room-only',
        'bed_and_breakfast' => 'board-badge-bb',
        'half_board' => 'board-badge-hb',
        'full_board' => 'board-badge-fb',
    ];

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
     * Human-readable board type, e.g. "Bed & Breakfast (BB)" for a known
     * BOARD_TYPES value. Falls back to a generic title-cased version of
     * whatever's stored (e.g. a value from before this list existed)
     * rather than showing nothing. Null only when board_type itself is
     * null, which no longer happens for new bookings now that the column
     * defaults to 'cabana_only', but can still be true for rows written
     * directly rather than through the model (a raw DB seed, for
     * instance).
     */
    protected function boardTypeLabel(): Attribute
    {
        return Attribute::get(fn () => $this->board_type
            ? (self::BOARD_TYPES[$this->board_type] ?? str(str_replace('_', ' ', $this->board_type))->title()->toString())
            : null);
    }

    /**
     * The CSS badge modifier class for this booking's board type pill,
     * defaulting to the "Cabana Only" styling for a null or unrecognized
     * value so a badge always renders something sensible.
     */
    protected function boardTypeBadgeClass(): Attribute
    {
        return Attribute::get(fn () => self::BOARD_TYPE_BADGE_CLASSES[$this->board_type] ?? 'board-badge-room-only');
    }

    /**
     * Short label for the board type pill (e.g. "HB"), as opposed to
     * board_type_label's fuller "Half Board (HB)" used in the form
     * dropdown, invoice, and quick view modal.
     */
    protected function boardTypeBadgeLabel(): Attribute
    {
        return Attribute::get(fn () => self::BOARD_TYPE_BADGE_LABELS[$this->board_type] ?? 'Cabana Only');
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
