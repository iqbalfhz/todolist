<?php

use App\Models\Space;
use App\Rules\SyncCursor;

// Closing, restoring, and deleting a space for good all go through this list,
// so a synced model left out of it would outlive its space or never come back.
test('the content models are every collection that sync sends besides the spaces', function () {
    $tables = collect(Space::CONTENT_MODELS)->map(fn (string $model): string => (new $model)->getTable());

    expect($tables->sort()->values()->all())->toBe(
        collect(SyncCursor::COLLECTIONS)->reject(fn (string $collection): bool => $collection === 'spaces')->sort()->values()->all(),
    );
});
