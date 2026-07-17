<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\BelongsToVilla;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use BelongsToVilla, HasFactory, Notifiable;

    /**
     * The full set of granular permissions a manager can be given. Owners
     * implicitly have all of these regardless of what's stored - see
     * hasPermission().
     *
     * @var list<string>
     */
    public const PERMISSIONS = [
        'manage_bookings' => 'Manage Bookings',
        'manage_expenses' => 'Manage Expenses',
        'view_finance_dashboard' => 'View Finance Dashboard',
        'generate_reports' => 'Generate Reports',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'villa_id',
        'name',
        'email',
        'password',
        'role',
        'permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Owners implicitly hold every permission - only a manager's stored
     * permissions array is actually consulted.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return in_array($permission, $this->permissions ?? [], true);
    }
}
