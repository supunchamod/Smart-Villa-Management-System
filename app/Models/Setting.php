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
    ];

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
        ]);
    }
}
