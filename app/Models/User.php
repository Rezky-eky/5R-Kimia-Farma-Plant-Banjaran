<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'npp',
        'password',
        'points_balance',
        'role',
        'bagian',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate NPP automatically if not provided
        static::creating(function ($user) {
            if (empty($user->npp)) {
                $user->npp = static::generateNPP();
            }
        });
    }

    /**
     * Generate unique NPP.
     */
    protected static function generateNPP(): string
    {
        do {
            // Generate 10-digit NPP (format: YYYYMMDD + random 2 digits)
            $npp = date('Ymd') . str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
        } while (static::where('npp', $npp)->exists());

        return $npp;
    }

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
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi dengan model Audit (sebagai auditor).
     */
    public function audits()
    {
        return $this->hasMany(Audit::class, 'auditor_id');
    }

    /**
     * Relasi dengan model GoAction.
     */
    public function goActions()
    {
        return $this->hasMany(GoAction::class);
    }

    /**
     * Relasi dengan model GoBoost.
     */
    public function goBoosts()
    {
        return $this->hasMany(GoBoost::class);
    }

    /**
     * Relasi dengan model GoCare.
     */
    public function goCares()
    {
        return $this->hasMany(GoCare::class);
    }

    /**
     * Relasi dengan model Notification.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Relasi dengan model GoBoost (sebagai user yang di-mention).
     */
    public function mentionedInGoBoosts()
    {
        return $this->hasMany(GoBoost::class, 'mentioned_user_id');
    }

    /**
     * Cek apakah user adalah admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'npp';
    }

    /**
     * Find the user instance for the given username (NPP) for Passport authentication.
     *
     * @param  string  $username
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function findForPassport($username)
    {
        return $this->where('npp', $username)->first();
    }
}
