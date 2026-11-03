<?php

namespace App\Actions;

use Illuminate\Support\Str;

class MaskEmail
{
    /**
     * Hide most of the part before the @, as in "ba***@gmail.com".
     *
     * Two characters stay when that part has three or more, and one when it
     * is shorter, so a short address is never shown whole. The domain stays.
     */
    public function handle(string $email): string
    {
        $local = Str::beforeLast($email, '@');
        $domain = Str::afterLast($email, '@');

        return Str::substr($local, 0, Str::length($local) >= 3 ? 2 : 1).'***@'.$domain;
    }
}
