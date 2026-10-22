<?php

namespace App\Enums;

enum SpecialDateType: string
{
    case Birthday = 'birthday';
    case Anniversary = 'anniversary';
    case Other = 'other';
}
