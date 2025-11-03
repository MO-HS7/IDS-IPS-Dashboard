<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Relationships
     */
    public function networkLogs()
    {
        return $this->hasMany(NetworkLog::class);
    }

    public function alerts()
    {
        return $this->belongsToMany(Alert::class, 'user_alerts')
                    ->withPivot('assigned_at')
                    ->withTimestamps();
    }

    public function notificationPreferences()
    {
        return $this->hasOne(UserNotificationPreference::class);
    }

    /**
     * Create notification preferences automatically when user is created
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            $user->notificationPreferences()->create([
                'email_alerts' => true,
                'browser_notifications' => true,
                'sound_notifications' => true,
                'critical_alerts_only' => false,
                'alert_types' => ['critical', 'high', 'medium', 'low']
            ]);
        });
    }

    /**
     * Role management methods
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('Admin');
    }
}
