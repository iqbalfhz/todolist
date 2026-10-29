<?php

namespace App\Enums;

use App\Models\Memory;
use App\Models\SavingsEntry;
use App\Models\Task;
use App\Rules\LowercaseUlid;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * What a comment or a reaction is about. The values are also the morph
 * aliases stored in subject_type.
 */
enum SubjectType: string
{
    case Task = 'task';
    case SavingsEntry = 'savings_entry';
    case Memory = 'memory';

    /**
     * Get the model a subject of this type is stored as.
     *
     * @return class-string<Task|SavingsEntry|Memory>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Task => Task::class,
            self::SavingsEntry => SavingsEntry::class,
            self::Memory => Memory::class,
        };
    }

    /**
     * Get the space of the subject a request names, or null when it names none that exists.
     */
    public static function spaceIdOf(mixed $type, mixed $id): ?string
    {
        $subjectType = is_string($type) ? self::tryFrom($type) : null;

        if ($subjectType === null || ! LowercaseUlid::matches($id)) {
            return null;
        }

        return $subjectType->modelClass()::withTrashed()->whereKey($id)->value('space_id');
    }

    /**
     * Store every subject under its short alias instead of its class name.
     *
     * Only these models are mapped, so the class names that other
     * polymorphic tables already store, such as the owners of tokens, stay valid.
     */
    public static function registerMorphMap(): void
    {
        Relation::morphMap(collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->modelClass()])
            ->all());
    }
}
