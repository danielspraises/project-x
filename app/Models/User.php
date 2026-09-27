<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

/**
 * Implements MustVerifyEmail so the infrastructure exists (Breeze already ships the
 * verification screens/mail), but nothing currently enforces it — every account here
 * is created by an admin (Super Admin or ICT Admin), not self-registered, and is
 * auto-marked verified at creation. This becomes meaningful once self-registration
 * exists (e.g. the Phase 2 student portal), at which point the 'verified' middleware
 * can be added to whichever routes need it without any further model changes.
 */
class User extends Authenticatable implements MustVerifyEmailContract
{
    use MustVerifyEmail, Notifiable;

    protected $fillable = [
        'institution_id', 'role_id', 'department_id', 'name', 'email', 'password', 'status', 'is_primary',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_primary' => 'boolean',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->institution_id === null && $this->role?->slug === 'super_admin';
    }

    public function hasPermission(string $slug): bool
    {
        // Super Admin implicitly has every permission.
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->role?->hasPermission($slug) ?? false;
    }

    /**
     * True if this user's access should be limited to their assigned department
     * (e.g. a HOD or Department Officer), rather than the whole institution.
     */
    public function isDepartmentScoped(): bool
    {
        return $this->department_id !== null;
    }
}
