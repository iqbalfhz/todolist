<?php

namespace App\Actions;

use App\Enums\SubjectType;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateComment
{
    public function __construct(
        private FindDiscussableSubject $findDiscussableSubject,
        private NotifyDiscussion $notifyDiscussion,
    ) {}

    /**
     * Store a comment with the ID the phone chose, and tell the other members of the space.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, string $id, array $data): Comment
    {
        return DB::transaction(function () use ($actor, $id, $data): Comment {
            $subject = $this->findDiscussableSubject->handle($actor, SubjectType::from($data['subject_type']), $data['subject_id']);

            $comment = new Comment(['body' => $data['body']]);
            $comment->id = $id;
            $comment->space_id = $subject->space_id;
            $comment->subject()->associate($subject);
            $comment->user_id = $actor->id;
            $comment->save();

            $this->notifyDiscussion->commented($comment, $subject, $actor);

            return $comment;
        });
    }
}
