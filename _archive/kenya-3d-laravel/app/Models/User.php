<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'account_type',
    'phone',
    'county_id',
    'id_number',
    'kra_pin',
    'business_reg',
    'phone_verified_at',
    'mfa_enabled',
    'mfa_secret',
    'status',
    'metadata',
])]
#[Hidden(['password', 'remember_token', 'mfa_secret'])]
class User extends Authenticatable
{
    use HasRoles;
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const TYPE_INDIVIDUAL = 'individual';
    public const TYPE_SME = 'sme';
    public const TYPE_SCHOOL = 'school';
    public const TYPE_COUNTY = 'county';
    public const TYPE_MINISTRY = 'ministry';
    public const TYPE_ADMIN = 'admin';
    public const TYPE_NIS = 'nis';
    public const TYPE_SUPERADMIN = 'superadmin';

    public static array $accountTypes = [
        self::TYPE_INDIVIDUAL,
        self::TYPE_SME,
        self::TYPE_SCHOOL,
        self::TYPE_COUNTY,
        self::TYPE_MINISTRY,
        self::TYPE_ADMIN,
        self::TYPE_NIS,
        self::TYPE_SUPERADMIN,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'mfa_enabled' => 'boolean',
            'metadata' => 'json',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->account_type === self::TYPE_ADMIN;
    }

    public function isCounty(): bool
    {
        return $this->account_type === self::TYPE_COUNTY;
    }

    public function isMinistry(): bool
    {
        return $this->account_type === self::TYPE_MINISTRY;
    }

    public function isSuperadmin(): bool
    {
        return $this->account_type === self::TYPE_SUPERADMIN;
    }

    public function isNis(): bool
    {
        return $this->account_type === self::TYPE_NIS;
    }

    public function isSme(): bool
    {
        return $this->account_type === self::TYPE_SME;
    }

    public function isSchool(): bool
    {
        return $this->account_type === self::TYPE_SCHOOL;
    }

    public function isIndividual(): bool
    {
        return $this->account_type === self::TYPE_INDIVIDUAL;
    }
}
