<?php

namespace App\Enums;

/**
 * What an emailed code is for.
 */
enum VerificationPurpose: string
{
    case PasswordReset = 'password_reset';
    case EmailVerification = 'email_verification';
    case EmailChange = 'email_change';
}
