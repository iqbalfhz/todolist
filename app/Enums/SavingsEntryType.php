<?php

namespace App\Enums;

enum SavingsEntryType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
}
