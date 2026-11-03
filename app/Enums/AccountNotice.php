<?php

namespace App\Enums;

/**
 * What a security notice email tells the owner of an account.
 */
enum AccountNotice: string
{
    case EmailChanged = 'email_changed';
    case PasswordChanged = 'password_changed';
}
