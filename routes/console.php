<?php

use App\Actions\PurgeClosedSpaces;
use App\Actions\SendDueReminders;
use App\Actions\SendSavingsReminders;
use App\Actions\SendSpecialDateReminders;
use App\Actions\SendTripReminders;
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

// Runs every minute, but sends only in spaces where it is between 08.00 and
// 08.30, and only once per reminder.
Artisan::command('special-dates:remind', function (SendSpecialDateReminders $sendSpecialDateReminders) {
    $sent = $sendSpecialDateReminders->handle();

    $this->info("Special date reminders sent: {$sent}.");
})->purpose('Send the push reminders of the special dates and birthdays that are coming up')
    ->everyMinute()
    ->withoutOverlapping(5);

// Runs every minute, but sends only in spaces where it is between 08.00 and
// 08.30, and only once per reminder.
Artisan::command('trips:remind', function (SendTripReminders $sendTripReminders) {
    $sent = $sendTripReminders->handle();

    $this->info("Trip reminders sent: {$sent}.");
})->purpose('Send the push reminders of the trips that start in 7 days or tomorrow')
    ->everyMinute()
    ->withoutOverlapping(5);

// Runs every minute, but sends only in spaces where it is between 08.00 and
// 08.30, and only once per goal per day.
Artisan::command('savings:remind', function (SendSavingsReminders $sendSavingsReminders) {
    $sent = $sendSavingsReminders->handle();

    $this->info("Savings reminders sent: {$sent}.");
})->purpose('Send the deposit reminders of the savings goals whose day is today')
    ->everyMinute()
    ->withoutOverlapping(5);

// Runs once a day, so a closed shared space is deleted at most a day after its
// 30 days run out. A run that is cut short leaves the rest for the next day.
Artisan::command('spaces:purge', function (PurgeClosedSpaces $purgeClosedSpaces) {
    $purged = $purgeClosedSpaces->handle();

    $this->info("Closed shared spaces deleted for good: {$purged}.");
})->purpose('Delete for good the shared spaces that were closed more than 30 days ago')
    ->daily()
    ->withoutOverlapping(60);
