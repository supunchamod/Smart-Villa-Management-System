<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'villa_name',
        'villa_logo',
        'address',
        'phone_number',
        'email',
        'currency',
        'website_logo_url',
        'website_hero_image_url',
        'website_hero_title',
        'website_hero_subtitle',
        'public_whatsapp_number',
        'half_board_rate',
        'full_board_rate',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'half_board_rate' => 'decimal:2',
            'full_board_rate' => 'decimal:2',
        ];
    }

    /**
     * This is a single-tenant, whitelabel installation, so there is only
     * ever one settings row. Falling back to an unsaved default instance
     * (rather than null) means every view/controller can read from this
     * without a null check, even before the seeder has run.
     */
    public static function current(): self
    {
        return static::query()->first() ?? new static([
            'villa_name' => 'My Villa',
            'currency' => 'LKR',
            'half_board_rate' => 3500,
            'full_board_rate' => 6000,
        ]);
    }
}
