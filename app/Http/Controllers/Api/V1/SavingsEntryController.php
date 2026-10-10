<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateSavingsEntry;
use App\Actions\DeleteSavingsEntry;
use App\Actions\UpdateSavingsEntry;
use App\Http\Requests\StoreSavingsEntryRequest;
use App\Http\Requests\UpdateSavingsEntryRequest;
use App\Http\Resources\SavingsEntryResource;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Rules\LowercaseUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

class SavingsEntryController extends QueuedResourceController
{
    /**
     * Record a deposit with the ID the phone chose.
     */
    public function store(Request $request, string $id, CreateSavingsEntry $createSavingsEntry): JsonResponse
    {
        return $this->createOnce($id, $this->targetSpaceId($request), fn (): SavingsEntry => $createSavingsEntry->handle(
            $request->user(),
            $id,
            resolve(StoreSavingsEntryRequest::class)->validated(),
        ));
    }

    /**
     * Change the fields of the entry that were sent.
     */
    public function update(Request $request, string $id, UpdateSavingsEntry $updateSavingsEntry): JsonResponse
    {
        $entry = $this->findOrFail($id);

        $this->ensureNotDeleted($entry);

        return $this->respond(
            $updateSavingsEntry->handle($request->user(), $entry, resolve(UpdateSavingsEntryRequest::class)->validated()),
            200,
        );
    }

    /**
     * Delete the entry.
     */
    public function destroy(Request $request, string $id, DeleteSavingsEntry $deleteSavingsEntry): Response
    {
        $entry = $this->findOrFail($id);

        if (! $entry->trashed()) {
            $deleteSavingsEntry->handle($request->user(), $entry);
        }

        return response()->noContent();
    }

    protected function model(): string
    {
        return SavingsEntry::class;
    }

    protected function resource(Model $record): JsonResource
    {
        return new SavingsEntryResource($record);
    }

    /**
     * Get the space of the goal the request names, when that goal exists.
     */
    private function targetSpaceId(Request $request): ?string
    {
        $goalId = $request->input('savings_goal_id');

        return LowercaseUlid::matches($goalId)
            ? SavingsGoal::withTrashed()->whereKey($goalId)->value('space_id')
            : null;
    }
}
