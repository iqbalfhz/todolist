<?php

use App\Models\Concerns\NotifiesSpaceChanges;
use App\Rules\SyncCursor;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

it('is used by the model of every collection that GET /sync sends, and by no other', function () {
    $tables = collect(File::files(app_path('Models')))
        ->map(fn (SplFileInfo $file): string => 'App\\Models\\'.$file->getBasename('.php'))
        ->filter(fn (string $model): bool => in_array(NotifiesSpaceChanges::class, class_uses_recursive($model), true))
        ->map(fn (string $model): string => (new $model)->getTable());

    expect($tables->values()->all())->toEqualCanonicalizing(SyncCursor::COLLECTIONS);
});
