<?php

namespace App\Http\Requests;

use App\Rules\Iso8601WithOffset;
use App\Rules\SyncCursor;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;

class SyncRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * A cursor already carries where its sync started, so since is left out when both are sent.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cursor' => ['sometimes', 'required', 'string', new SyncCursor],
            'since' => ['exclude_with:cursor', 'sometimes', 'required', 'string', new Iso8601WithOffset],
        ];
    }

    /**
     * Get the server time of the phone's last sync, or null when it asks for everything.
     */
    public function since(): ?CarbonImmutable
    {
        $since = $this->validated('since');

        return $since === null ? null : Iso8601WithOffset::toServerTime($since);
    }

    /**
     * Get the state carried by the cursor of the previous page, or null on a first page.
     *
     * @return array{server_time: string, with_deleted: bool, positions: array<string, array{0: string, 1: string}|null>}|null
     */
    public function cursor(): ?array
    {
        $cursor = $this->validated('cursor');

        return $cursor === null ? null : SyncCursor::decode($cursor);
    }
}
