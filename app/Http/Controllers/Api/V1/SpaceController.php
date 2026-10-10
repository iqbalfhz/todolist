<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SpaceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
}
