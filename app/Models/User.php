<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    public const ROLE_USER = 'user';

    public const ROLE_ADMIN = 'admin';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'country',
        'timezone',
        'locale',
        'currency',
        'role',
        'status',
        'referral_code',
        'referred_by',
        'referred_at',
        'credits_balance',
        'wallet_balance',
        'email_notifications',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $appends = ['initials'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'credits_balance' => 'decimal:2',
            'wallet_balance' => 'decimal:2',
            'referred_at' => 'datetime',
            'email_notifications' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'login_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if (blank($user->referral_code)) {
                $user->referral_code = static::generateReferralCode();
            }
        });
    }

    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : mb_strtoupper(mb_substr($this->email, 0, 2));
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled && filled($this->two_factor_secret);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class)->latest();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ContentDocument::class)->latest();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->latest();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest();
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class)->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class)->latest();
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'trialing'])
            ->latestOfMany();
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class)->latest();
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(AffiliateEarning::class)->latest();
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class)->latest();
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function affiliateUrl(): string
    {
        return rtrim(config('app.url'), '/').'/ref/'.$this->referral_code;
    }

    public function totalCredits(): float
    {
        return (float) $this->creditTransactions()->sum('amount');
    }

    public function grantCredits(int $amount, string $type, string $description, ?string $referenceType = null, ?int $referenceId = null): void
    {
        $this->increment('credits_balance', $amount);

        $this->creditTransactions()->create([
            'type' => $type,
            'amount' => $amount,
            'balance_after' => (float) $this->fresh()->credits_balance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    public function spendCredits(int $amount, string $description, ?string $referenceType = null, ?int $referenceId = null): bool
    {
        if ((float) $this->credits_balance < $amount) {
            return false;
        }

        $this->grantCredits(-$amount, 'usage', $description, $referenceType, $referenceId);

        return true;
    }

    public function recordLogin(?string $ip): void
    {
        $this->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'login_count' => $this->login_count + 1,
        ])->save();
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
        $this->save();
    }
}
