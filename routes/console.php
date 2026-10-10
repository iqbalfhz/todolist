<?php

use App\Actions\SendDueReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The lock is released after 5 minutes, so a run that was killed by a deploy
// does not hold back the reminders of the rest of the day.
Artisan::command('reminders:send', function (SendDueReminders $sendDueReminders) {
    ['sent' => $sent, 'late' => $late] = $sendDueReminders->handle();

    $this->info("Reminders sent: {$sent}. Too late to send: {$late}.");
})->purpose('Send the push reminders of the tasks that are due')
    ->everyMinute()
    ->withoutOverlapping(5);
