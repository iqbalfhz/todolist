<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ExportAccountData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataExportController extends Controller
{
    /**
     * Download a copy of everything the user can see in the app, as one JSON file.
     *
     * The file stands on its own, without the data wrapper of other
     * responses, and is named after the day in the user's timezone.
     */
    public function show(Request $request, ExportAccountData $exportAccountData): JsonResponse
    {
        $user = $request->user();
        $day = now()->setTimezone($user->timezone)->toDateString();

        return response()->json(
            $exportAccountData->handle($user, $request),
            200,
            ['Content-Disposition' => "attachment; filename=\"denly-days-{$day}.json\""],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}
