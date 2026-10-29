<?php

namespace App\Actions;

use App\Enums\SubjectType;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateReaction
{
    public function __construct(
        private FindDiscussableSubject $findDiscussableSubject,
        private NotifyDiscussion $notifyDiscussion,
    ) {}

    /**
     * Store a reaction with the ID the phone chose, in place of the actor's earlier reaction to the same subject.
     *
     * The subject stays locked while the earlier reaction is deleted and the
     * new one is stored, so two reactions sent at once leave only one. The
     * other members hear about it once the reaction is committed, since the
     * push also starts a quiet period that must not outlive a failed save.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, string $id, array $data): Reaction
    {
        [$reaction, $subject] = DB::transaction(function () use ($actor, $id, $data): array {
            $subject = $this->findDiscussableSubject->handle($actor, SubjectType::from($data['subject_type']), $data['subject_id']);

            // An Eloquent delete bumps updated_at, so the replaced reaction disappears from the other phone through sync.
            $subject->reactions()->where('user_id', $actor->id)->delete();

            $reaction = new Reaction(['emoji' => $data['emoji']]);
            $reaction->id = $id;
            $reaction->space_id = $subject->space_id;
            $reaction->subject()->associate($subject);
            $reaction->user_id = $actor->id;
            $reaction->save();

            return [$reaction, $subject];
        });

        $this->notifyDiscussion->reacted($reaction, $subject, $actor);

        return $reaction;
    }
}
