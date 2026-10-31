<?php

namespace App\Actions;

use App\Http\Resources\SyncResource;
use App\Http\Resources\UserResource;
use App\Models\DeviceToken;
use App\Models\User;
use App\Rules\SyncCursor;
use Illuminate\Http\Request;

class ExportAccountData
{
    /**
     * The version of the export file, raised whenever its shape changes.
     */
    public const FORMAT_VERSION = 1;

    public function __construct(private QuerySyncCollection $querySyncCollection) {}

    /**
     * Gather everything the user can see in the app into one export.
     *
     * The collections are the ones a full sync sends, in the same shape:
     * the open spaces of the user and what they hold that is not deleted,
     * the partner's entries in the shared space included. Closed spaces and
     * the partner's personal space are never among them. Secrets stay out:
     * no password, tokens, or Google account ID.
     *
     * @return array<string, mixed>
     */
    public function handle(User $user, Request $request): array
    {
        $spaceIds = $user->spaces()->pluck('spaces.id');

        $collections = collect(SyncCursor::COLLECTIONS)
            ->mapWithKeys(fn (string $collection): array => [
                $collection => $this->querySyncCollection->handle($collection, $spaceIds)
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->get(),
            ])
            ->all();

        return [
            'app' => config('app.name'),
            'format_version' => self::FORMAT_VERSION,
            'exported_at' => now(),
            'user' => new UserResource($user),
            'devices' => $user->deviceTokens()
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
                ->map(fn (DeviceToken $device): array => [
                    'platform' => $device->platform,
                    'created_at' => $device->created_at,
                    'updated_at' => $device->updated_at,
                ])
                ->all(),
            ...(new SyncResource(['changes' => $collections, 'server_time' => now()->toImmutable(), 'next_cursor' => null]))->toArray($request),
        ];
    }
}
