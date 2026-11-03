<?php

use App\Actions\FetchExchangeRates;
use App\Actions\PurgeClosedSpaces;
use App\Actions\PurgeDeletedRecords;
use App\Actions\SendDueReminders;
use App\Actions\SendMemoryFlashbacks;
use App\Actions\SendSavingsReminders;
use App\Actions\SendSpecialDateReminders;
use App\Actions\SendTripReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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

// Runs every minute, but sends only in spaces where it is between 08.00 and
// 08.30, and only once per space per day.
Artisan::command('memories:remind', function (SendMemoryFlashbacks $sendMemoryFlashbacks) {
    $sent = $sendMemoryFlashbacks->handle();

    $this->info("Memory flashbacks sent: {$sent}.");
})->purpose('Send the push reminders of the memories made on this day in earlier years')
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

// Runs once a day, half an hour after spaces:purge, so a deleted row goes at
// most a day after its 30 days run out. A run that is cut short leaves the
// rest for the next day.
Artisan::command('records:purge', function (PurgeDeletedRecords $purgeDeletedRecords) {
    ['records' => $records, 'reminders' => $reminders, 'invitations' => $invitations, 'codes' => $codes, 'cache' => $cache] = $purgeDeletedRecords->handle();

    $this->info("Deleted records purged: {$records}. Old reminders: {$reminders}. Old invitations: {$invitations}. Expired codes: {$codes}. Expired cache entries: {$cache}.");
})->purpose('Delete for good the records deleted more than 30 days ago, and the old records of the server')
    ->dailyAt('00:30')
    ->withoutOverlapping(60);

// The ECB publishes new rates once a working day, around 16.00 in Frankfurt.
// Running every hour picks them up within the hour, and a fetch that fails,
// or misses its turn during a deploy, is made up an hour later.
Artisan::command('exchange-rates:fetch', function (FetchExchangeRates $fetchExchangeRates) {
    $exchangeRate = $fetchExchangeRates->handle();

    $this->info($exchangeRate === null
        ? 'Exchange rates not updated. The reason is in the log.'
        : "Exchange rates of {$exchangeRate->rate_date} stored.");
})->purpose('Fetch the daily reference exchange rates of the ECB for the savings estimates')
    ->hourly()
    ->withoutOverlapping(10);

// A push that failed for good keeps its title and body, which hold the user's content.
Schedule::command('queue:prune-failed', ['--hours' => 24 * PurgeDeletedRecords::RETENTION_DAYS])->daily();
