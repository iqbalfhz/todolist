<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Database\Factories\SpaceInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['code', 'created_by', 'space_id', 'accepted_by', 'expires_at', 'accepted_at', 'revoked_at'])]
class SpaceInvitation extends Model
{
    /** @use HasFactory<SpaceInvitationFactory> */
    use HasFactory, HasMicrosecondTimestamps, HasUlids;

    /**
     * The characters a code is drawn from: no 0, O, 1, or I.
     */
    public const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    /**
     * The number of characters in a code.
     */
    public const CODE_LENGTH = 6;

    /**
     * The number of hours a code stays valid.
     */
    public const VALID_FOR_HOURS = 48;

    /**
     * Get the user who created the invitation.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who accepted the invitation.
     */
    public function acceptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    /**
     * Get the shared space that was created when the invitation was accepted.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Get where the invitation stands. Only one that is neither accepted nor revoked can expire.
     *
     * @return Attribute<InvitationStatus, never>
     */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): InvitationStatus => match (true) {
            $this->accepted_at !== null => InvitationStatus::Accepted,
            $this->revoked_at !== null => InvitationStatus::Revoked,
            $this->expires_at->lessThanOrEqualTo(now()) => InvitationStatus::Expired,
            default => InvitationStatus::Active,
        });
    }

    /**
     * Scope the query to invitations that can still be accepted.
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
