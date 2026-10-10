<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\BuildSyncPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\SyncRequest;
use App\Http\Resources\SyncResource;

class SyncController extends Controller
{
    /**
     * Show one page of the rows in the user's spaces: all of them, or those changed since the last sync.
     */
    public function show(SyncRequest $request, BuildSyncPage $buildSyncPage): SyncResource
    {
        return new SyncResource(
            $buildSyncPage->handle($request->user(), $request->since(), $request->cursor()),
        );
    }
}
