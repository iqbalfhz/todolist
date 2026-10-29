<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\SearchPlaces;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchPlacesRequest;
use Illuminate\Http\JsonResponse;

class PlaceController extends Controller
{
    /**
     * Find places for a memory pin. The app calls it only when the user presses search.
     */
    public function index(SearchPlacesRequest $request, SearchPlaces $searchPlaces): JsonResponse
    {
        return response()->json(['data' => $searchPlaces->handle($request->validated('q'))]);
    }
}
