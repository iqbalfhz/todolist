<?php

use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

arch('models use ULID keys and microsecond timestamps')
    ->expect('App\Models')
    ->classes()
    ->toUseTraits([HasUlids::class, HasMicrosecondTimestamps::class]);
