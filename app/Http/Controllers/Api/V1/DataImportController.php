<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ImportData;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportDataRequest;
use Illuminate\Http\JsonResponse;

class DataImportController extends Controller
{
    /**
     * Save one part of an export file as new data, all of its rows or none.
     *
     * Not sent from the offline queue. The phone sends the parts one after
     * another, and a part sent again skips the rows it saved before.
     */
    public function store(ImportDataRequest $request, ImportData $importData): JsonResponse
    {
        return response()->json(['data' => $importData->handle($request->user(), $request->validated())]);
    }
}
