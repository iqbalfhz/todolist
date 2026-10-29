<?php

namespace App\Actions;

use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\Comment;
use App\Models\Memory;
use App\Models\Reaction;
use App\Models\SavingsEntry;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class NotifyDiscussion
{
    /**
     * The most characters of a comment that its push shows.
     */
    public const PREVIEW_LENGTH = 80;

    /**
     * How long a reaction push keeps later reactions to the same subject from pushing again to its recipient.
     */
    public const REACTION_QUIET_MINUTES = 10;

    /**
     * Tell the other members of the space that the actor commented on the subject.
     */
    public function commented(Comment $comment, Task|SavingsEntry|Memory $subject, User $actor): void
    {
        $body = __('push.comment_created.body', ['name' => $actor->name, 'comment' => $this->preview($comment->body)]);

        foreach ($this->otherMemberIds($subject, $actor) as $memberId) {
            $this->send($memberId, PushType::CommentCreated, $subject, $body);
        }
    }

    /**
     * Tell the other members of the space that the actor reacted to the
     * subject, at most once every ten minutes per subject for each of them.
     */
    public function reacted(Reaction $reaction, Task|SavingsEntry|Memory $subject, User $actor): void
    {
        $body = __('push.reaction_created.body', ['name' => $actor->name, 'emoji' => $reaction->emoji]);

        foreach ($this->otherMemberIds($subject, $actor) as $memberId) {
            $quietPeriod = now()->addMinutes(self::REACTION_QUIET_MINUTES);

            if (Cache::add("reaction-push:{$memberId}:{$subject->id}", true, $quietPeriod)) {
                $this->send($memberId, PushType::ReactionCreated, $subject, $body);
            }
        }
    }

    /**
     * Queue the push about the subject once the data behind it is committed.
     */
    private function send(string $recipientId, PushType $type, Task|SavingsEntry|Memory $subject, string $body): void
    {
        SendPushNotification::dispatch(
            $recipientId,
            $type,
            $this->titleOf($subject),
            $body,
            $subject->space_id,
            savingsGoalId: $subject instanceof SavingsEntry ? $subject->savings_goal_id : null,
            subjectType: $subject->getMorphClass(),
            subjectId: $subject->id,
        )->afterCommit();
    }

    /**
     * Get the IDs of the members of the subject's space other than the actor.
     *
     * @return Collection<int, string>
     */
    private function otherMemberIds(Task|SavingsEntry|Memory $subject, User $actor): Collection
    {
        return $subject->space->members()->whereKeyNot($actor->id)->pluck('users.id');
    }

    /**
     * Get the title of the push: the title of a task or a memory, or the name of the goal of an entry.
     */
    private function titleOf(Task|SavingsEntry|Memory $subject): string
    {
        if ($subject instanceof SavingsEntry) {
            return (string) $subject->savingsGoal()->withTrashed()->value('name');
        }

        return $subject->title;
    }

    /**
     * Shorten a comment for the push, on one line.
     */
    private function preview(string $body): string
    {
        $text = Str::squish($body);

        if (mb_strlen($text) <= self::PREVIEW_LENGTH) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, self::PREVIEW_LENGTH)).'…';
    }
}
