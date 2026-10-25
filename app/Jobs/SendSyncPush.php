<?php

namespace App\Jobs;

use App\Actions\SendToDevices;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\CloudMessage;

/**
 * Tells the phones of one member that their shared space changed, so an open
 * app syncs right away. The message carries data only and never shows.
 */
class SendSyncPush implements ShouldQueue
{
    use Queueable;

    /**
     * How long, in seconds, the changes after a push wait to be sent together.
     */
    public const WINDOW_SECONDS = 10;

    /**
     * The number of times the job may be attempted.
     *
     * A lost push only delays the sync until the app's own timer, so it is not retried.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     *
     * @param  bool  $gathered  Whether the push sends the changes gathered during a window, which starts the next one.
     */
    public function __construct(
        public string $userId,
        public string $spaceId,
        public bool $gathered = false,
    ) {}

    /**
     * Queue a push for the member, at most one per window.
     *
     * The first change goes out at once. Changes during the window that follows
     * are gathered into one push at its end.
     */
    public static function queueFor(string $userId, string $spaceId): void
    {
        $windowEndsAt = now()->addSeconds(self::WINDOW_SECONDS)->getTimestamp();

        if (Cache::add(self::windowKey($userId), $windowEndsAt, self::WINDOW_SECONDS)) {
            self::dispatch($userId, $spaceId);

            return;
        }

        if (Cache::add(self::waitingKey($userId), true, self::WINDOW_SECONDS)) {
            $sendAt = Cache::get(self::windowKey($userId), now()->getTimestamp());

            self::dispatch($userId, $spaceId, gathered: true)->delay(Carbon::createFromTimestamp($sendAt));
        }
    }

    /**
     * Get the cache key that holds when the member's current window ends.
     */
    public static function windowKey(string $userId): string
    {
        return "sync-push:{$userId}";
    }

    /**
     * Get the cache key that marks a push already waiting for the end of the window.
     */
    public static function waitingKey(string $userId): string
    {
        return "sync-push:{$userId}:waiting";
    }

    /**
     * Send the push to every device of the member.
     */
    public function handle(): void
    {
        if ($this->gathered) {
            Cache::put(
                self::windowKey($this->userId),
                now()->addSeconds(self::WINDOW_SECONDS)->getTimestamp(),
                self::WINDOW_SECONDS,
            );
            Cache::forget(self::waitingKey($this->userId));
        }

        $user = User::query()->find($this->userId);

        if ($user === null) {
            return;
        }

        resolve(SendToDevices::class)->handle($user, $this->message(), 'sync');
    }

    /**
     * Build the FCM message: data only, so the phone shows nothing.
     */
    private function message(): CloudMessage
    {
        return CloudMessage::new()
            ->withData(['type' => 'sync', 'space_id' => $this->spaceId])
            ->withAndroidConfig(AndroidConfig::fromArray([
                'priority' => 'normal',
                'collapse_key' => "sync-{$this->spaceId}",
            ]));
    }
}
