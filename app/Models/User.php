<?php

namespace App\Models;

use App\Casts\EncryptedInteger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Cashier\Billable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, Billable, TwoFactorAuthenticatable;

    /**
     * The accessors that should be appended to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'is_subscribed',
        'is_profile_complete',
    ];

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'address',
        'city',
        'cp',
        'country',
        'age',
        'password',
        'is_admin',
        'is_editor',
        'is_super_admin',
        'is_security_officer',
        'display_name_color',
        'display_alias',
        'profile_border_style',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'encrypted',
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'phone' => 'encrypted',
            'address' => 'encrypted',
            'city' => 'encrypted',
            'cp' => 'encrypted',
            'country' => 'encrypted',
            'age' => EncryptedInteger::class,
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_editor' => 'boolean',
            'is_super_admin' => 'boolean',
            'is_security_officer' => 'boolean',
            'display_name_color' => 'encrypted',
            'display_alias' => 'encrypted',
            'profile_border_style' => 'encrypted',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function ratings()
    {
        return $this->hasMany(GameRating::class);
    }

    public function compatibilityScans(): HasMany
    {
        return $this->hasMany(CompatibilityScan::class);
    }

    public function articleReactions(): HasMany
    {
        return $this->hasMany(ArticleReaction::class);
    }

    public function oauthAccounts()
    {
        return $this->hasMany(OauthAccount::class);
    }

    public function dataErasureRequests(): HasMany
    {
        return $this->hasMany(DataErasureRequest::class);
    }

    public function getIsSubscribedAttribute(): bool
    {
        return $this->subscribed('default');
    }

    public function getIsProfileCompleteAttribute(): bool
    {
        $requiredFields = [
            'name',
            'username',
            'email',
            'age',
            'phone',
            'address',
            'city',
            'cp',
            'country',
        ];

        foreach ($requiredFields as $field) {
            if (blank($this->{$field})) {
                return false;
            }
        }

        return true;
    }

}
