<?php

namespace App\Jobs;

use App\Actions\SendToDevices;
use App\Enums\PushType;
use App\Enums\SubjectType;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
     * The type of the task, savings entry, or memory a comment or reaction push is about.
     *
     * Declared with a default rather than promoted, so a push queued before
     * this field existed is still read as having none after a deploy.
     */
    public ?string $subjectType = null;

    /**
     * The ID of the task, savings entry, or memory a comment or reaction push is about.
     */
    public ?string $subjectId = null;

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
        public ?string $memoryId = null,
        ?string $subjectType = null,
        ?string $subjectId = null,
    ) {
        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId;
    }

    /**
     * Send the push to every device of the recipient, unless they turned this kind off.
     */
    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null || ! $this->isWantedBy($user)) {
            return;
        }

        resolve(SendToDevices::class)->handle($user, $this->message(), $this->type->value);
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
                'memory_id' => $this->memoryId,
                'subject_type' => $this->subjectType,
                'subject_id' => $this->subjectId,
            ]))
            ->withAndroidConfig(AndroidConfig::fromArray([
                'priority' => $this->type->priority(),
                'notification' => array_filter([
                    'channel_id' => $this->type->channel(),
                    'tag' => $this->tag(),
                    'visibility' => $this->isPrivate() ? 'PRIVATE' : null,
                ]),
            ]));
    }

    /**
     * Determine whether the content must stay hidden on the lock screen, as
     * for every push about savings, comments and reactions on entries included.
     */
    private function isPrivate(): bool
    {
        return $this->type->isPrivate() || $this->subjectType === SubjectType::SavingsEntry->value;
    }

    /**
     * Get the tag that lets a newer notification about the same thing replace the older one.
     */
    private function tag(): string
    {
        if ($this->subjectId !== null) {
            return $this->type === PushType::ReactionCreated ? "reaction-{$this->subjectId}" : "comment-{$this->subjectId}";
        }

        return match (true) {
            $this->taskId !== null => "task-{$this->taskId}",
            $this->savingsGoalId !== null => "goal-{$this->savingsGoalId}",
            $this->specialDateId !== null => "date-{$this->specialDateId}",
            $this->memberId !== null => "birthday-{$this->memberId}",
            $this->tripId !== null => "trip-{$this->tripId}",
            $this->memoryId !== null => "memory-{$this->memoryId}",
            default => "space-{$this->spaceId}",
        };
    }
}
