<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ConfirmAccountOwner;
use App\Actions\ConfirmEmailChange;
use App\Actions\IssueVerificationCode;
use App\Enums\VerificationPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmEmailRequest;
use App\Http\Requests\StoreEmailChangeRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class EmailChangeController extends Controller
{
    /**
     * Email a code to the new address once the owner confirms the change.
     *
     * Not sent from the offline queue. The email changes only when the code comes back.
     */
    public function store(StoreEmailChangeRequest $request, ConfirmAccountOwner $confirmAccountOwner, IssueVerificationCode $issueVerificationCode): Response
    {
        $user = $request->user();

        $confirmAccountOwner->handle(
            $user,
            $request->filled('password') ? $request->validated('password') : null,
            $request->validated('id_token'),
            __('api.account.wrong_password'),
        );

        $issueVerificationCode->handle($user, VerificationPurpose::EmailChange, $request->validated('email'));

        return response()->noContent(202);
    }

    /**
     * Change the email with the code that was sent to the new address.
     */
    public function update(ConfirmEmailRequest $request, ConfirmEmailChange $confirmEmailChange): UserResource
    {
        $user = $request->user();

        if (! $confirmEmailChange->handle($user, $request->validated('code'))) {
            throw ValidationException::withMessages(['code' => __('api.validation.verification_code')]);
        }

        return new UserResource($user);
    }
}
