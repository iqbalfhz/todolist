<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CloseSharedSpace;
use App\Http\Controllers\Controller;
use App\Http\Resources\SpaceResource;
use App\Rules\LowercaseUlid;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class SpaceController extends Controller
{
    /**
     * List every space the signed-in user is a member of, with its members.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $spaces = $request->user()->spaces()
            ->with('members')
            ->orderBy('spaces.created_at')
            ->orderBy('spaces.id')
            ->get();

        return SpaceResource::collection($spaces);
    }

    /**
     * Leave the shared space, which closes it for both partners.
     *
     * Not sent from the offline queue: the phone sends its queue first, so
     * the last changes are saved before the space closes.
     */
    public function destroy(Request $request, string $id, CloseSharedSpace $closeSharedSpace): Response
    {
        if (! LowercaseUlid::matches($id)) {
            throw ValidationException::withMessages([
                'id' => __('api.validation.lowercase_ulid', ['attribute' => __('api.attributes.id')]),
            ]);
        }

        $closeSharedSpace->handle($request->user(), $id);

        return response()->noContent();
    }
}
