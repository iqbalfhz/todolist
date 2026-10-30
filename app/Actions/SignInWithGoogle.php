<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SignInWithGoogle
{
    /**
     * The most characters a name may have, as in registration.
     */
    private const NAME_MAX_LENGTH = 50;

    public function __construct(private CreatePersonalSpace $createPersonalSpace) {}

    /**
     * Find the account of a Google identity, linking or creating it as needed.
     *
     * The Google account is looked up first, so a changed Google email keeps
     * working, then the email. When two requests create the same account at
     * once, the one that loses the race signs in to the account the other made.
     *
     * @param  array{sub: string, email: string, name: string|null}  $identity
     * @return array{0: User, 1: bool} The user, and whether the account was just created.
     */
    public function handle(array $identity, ?string $timezone): array
    {
        try {
            return DB::transaction(fn (): array => $this->findOrCreate($identity, $timezone));
        } catch (UniqueConstraintViolationException) {
            return DB::transaction(fn (): array => $this->findOrCreate($identity, $timezone));
        }
    }

    /**
     * Sign in to the linked account, link the account with the same email, or create one.
     *
     * @param  array{sub: string, email: string, name: string|null}  $identity
     * @return array{0: User, 1: bool}
     */
    private function findOrCreate(array $identity, ?string $timezone): array
    {
        $user = User::query()->where('google_id', $identity['sub'])->first()
            ?? $this->link($identity);

        if ($user !== null) {
            // Accounts created outside registration, such as the admin, have no personal space yet.
            if ($user->personalSpace() === null) {
                $this->createPersonalSpace->handle($user);
            }

            return [$user, false];
        }

        $user = new User(array_filter([
            'name' => $this->nameOf($identity),
            'email' => $identity['email'],
            'timezone' => $timezone,
        ]));
        $user->google_id = $identity['sub'];
        $user->email_verified_at = now();
        $user->save();

        $this->createPersonalSpace->handle($user);

        return [$user, true];
    }

    /**
     * Link the account registered with the same email to the Google account.
     *
     * The password of that account keeps working.
     *
     * @param  array{sub: string, email: string, name: string|null}  $identity
     */
    private function link(array $identity): ?User
    {
        $user = User::query()->where('email', $identity['email'])->first();

        if ($user !== null) {
            $user->google_id = $identity['sub'];
            $user->save();
        }

        return $user;
    }

    /**
     * Get the name for a new account: the one Google gives, or the part of the email before the @.
     *
     * @param  array{sub: string, email: string, name: string|null}  $identity
     */
    private function nameOf(array $identity): string
    {
        $name = Str::squish($identity['name'] ?? '');

        if ($name === '') {
            $name = Str::before($identity['email'], '@');
        }

        return rtrim(mb_substr($name, 0, self::NAME_MAX_LENGTH));
    }
}
