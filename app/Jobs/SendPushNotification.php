<?php

namespace App\Jobs;

use App\Enums\PushType;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class SendPushNotification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before each retry.
     *
     * @var list<int>
     */
    public array $backoff = [10, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $userId,
        public PushType $type,
        public string $title,
        public string $body,
        public string $spaceId,
        public ?string $taskId = null,
        public ?string $savingsGoalId = null,
        public ?string $specialDateId = null,
        public ?string $memberId = null,
        public ?string $tripId = null,
    ) {}

    /**
     * Send the push to every device of the recipient, unless they turned this kind off.
     */
    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null || ! $this->isWantedBy($user)) {
            return;
        }

        $tokens = $user->deviceTokens()->pluck('token')->all();

        if ($tokens === []) {
            return;
        }

        // FCM is set up only when there is something to send, so a push to
        // someone without a device does not need Firebase credentials.
        $report = resolve(Messaging::class)->sendMulticast($this->message(), $tokens);

        // FCM answers UNREGISTERED for a token whose app was uninstalled or signed out.
        DeviceToken::query()->whereIn('token', $report->unknownTokens())->delete();
    }

    /**
     * Determine whether the recipient's preferences allow this push.
     */
    private function isWantedBy(User $user): bool
    {
        $preference = $this->type->preference();

        return $preference === null || $user->notification_prefs[$preference];
    }

    /**
     * Build the FCM message. Every data value is a string, as FCM requires.
     */
    private function message(): CloudMessage
    {
        return CloudMessage::new()
            ->withNotification(Notification::create($this->title, $this->body))
            ->withData(array_filter([
                'type' => $this->type->value,
                'space_id' => $this->spaceId,
                'task_id' => $this->taskId,
                'savings_goal_id' => $this->savingsGoalId,
                'special_date_id' => $this->specialDateId,
                'member_id' => $this->memberId,
                'trip_id' => $this->tripId,
            ]))
            ->withAndroidConfig(AndroidConfig::fromArray([
                'priority' => $this->type->priority(),
                'notification' => array_filter([
                    'channel_id' => $this->type->channel(),
                    'tag' => $this->tag(),
                    'visibility' => $this->type->isPrivate() ? 'PRIVATE' : null,
                ]),
            ]));
    }

    /**
     * Get the tag that lets a newer notification about the same thing replace the older one.
     */
    private function tag(): string
    {
        return match (true) {
            $this->taskId !== null => "task-{$this->taskId}",
            $this->savingsGoalId !== null => "goal-{$this->savingsGoalId}",
            $this->specialDateId !== null => "date-{$this->specialDateId}",
            $this->memberId !== null => "birthday-{$this->memberId}",
            $this->tripId !== null => "trip-{$this->tripId}",
            default => "space-{$this->spaceId}",
        };
    }
}
