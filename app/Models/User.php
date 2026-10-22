<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\SpaceType;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'color', 'timezone', 'default_currency', 'birth_date', 'notification_prefs'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasMicrosecondTimestamps, HasUlids, Notifiable;

    /**
     * The filesystem disk that holds profile photos.
     */
    public const AVATAR_DISK = 'r2';

    /**
     * The notification preferences an account starts with.
     *
     * @var array<string, bool>
     */
    public const DEFAULT_NOTIFICATION_PREFS = [
        'task_reminders' => true,
        'partner_activity' => true,
        'savings_activity' => true,
        'special_dates' => true,
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'color' => 'mawar',
        'timezone' => 'Asia/Jakarta',
        'locale' => 'id',
        'default_currency' => 'IDR',
        'avatar_version' => 0,
    ];

    /**
     * Determine whether the user may access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        $adminEmail = config('admin.email');

        return filled($adminEmail) && Str::lower($this->email) === Str::lower($adminEmail);
    }

    /**
     * Get the spaces the user is a member of.
     */
    public function spaces(): BelongsToMany
    {
        return $this->belongsToMany(Space::class, 'space_members')->withPivot(['role', 'joined_at']);
    }

    /**
     * Get the devices that receive the user's push notifications.
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Get the invitations the user has created.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(SpaceInvitation::class, 'created_by');
    }

    /**
     * Get the user's personal space, if it has been created.
     */
    public function personalSpace(): ?Space
    {
        return $this->spaces()->where('type', SpaceType::Personal)->first();
    }

    /**
     * Get the space the user shares with their partner, if they have one.
     */
    public function sharedSpace(): ?Space
    {
        return $this->spaces()->where('type', SpaceType::Shared)->first();
    }

    /**
     * Determine whether the user is a member of the given space.
     */
    public function isMemberOf(string $spaceId): bool
    {
        return $this->spaces()->whereKey($spaceId)->exists();
    }

    /**
     * Get a signed URL for the profile photo, valid for seven days, the longest R2 allows.
     */
    public function avatarUrl(): ?string
    {
        if ($this->avatar_path === null) {
            return null;
        }

        return Storage::disk(self::AVATAR_DISK)->temporaryUrl($this->avatar_path, now()->addDays(7));
    }

    /**
     * Get the notification preferences, with every known key present.
     *
     * @return Attribute<array<string, bool>, array<string, bool>>
     */
    protected function notificationPrefs(): Attribute
    {
        $known = array_keys(self::DEFAULT_NOTIFICATION_PREFS);

        return Attribute::make(
            get: fn (?string $value): array => [
                ...self::DEFAULT_NOTIFICATION_PREFS,
                ...Arr::only(json_decode($value ?? '[]', true) ?? [], $known),
            ],
            set: fn (array $value): string => json_encode(Arr::only($value, $known)),
        );
    }

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
            'avatar_version' => 'integer',
        ];
    }
}
