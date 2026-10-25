<?php

namespace App\Actions;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;

class SendToDevices
{
    /**
     * Send the message to every device of the user.
     *
     * Tokens that FCM will never deliver to are removed, and other failures are logged.
     */
    public function handle(User $user, CloudMessage $message, string $type): void
    {
        $tokens = $user->deviceTokens()->pluck('token')->all();

        if ($tokens === []) {
            return;
        }

        // FCM is set up only when there is something to send, so a push to
        // someone without a device does not need Firebase credentials.
        $report = resolve(Messaging::class)->sendMulticast($message, $tokens);

        // FCM answers UNREGISTERED for a token whose app was uninstalled or
        // signed out, and rejects a malformed token; neither will ever work.
        DeviceToken::query()->whereIn('token', [...$report->unknownTokens(), ...$report->invalidTokens()])->delete();

        $this->logOtherFailures($report, $type, $user->id);
    }

    /**
     * Log the failures that are not about a dead token, such as an outage or
     * a credentials problem, which would otherwise go unnoticed.
     *
     * The tokens themselves stay out of the log.
     */
    private function logOtherFailures(MulticastSendReport $report, string $type, string $userId): void
    {
        $failures = $report->failures()->filter(
            fn (SendReport $item): bool => ! $item->messageWasSentToUnknownToken() && ! $item->messageTargetWasInvalid(),
        );

        if ($failures->count() === 0) {
            return;
        }

        Log::warning('FCM did not deliver a push.', [
            'type' => $type,
            'user_id' => $userId,
            'failed_devices' => $failures->count(),
            'errors' => array_values(array_unique($failures->map(
                fn (SendReport $item): string => $item->error()?->getMessage() ?? 'unknown',
            ))),
        ]);
    }
}
