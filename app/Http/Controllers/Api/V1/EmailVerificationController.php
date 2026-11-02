<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ConfirmEmail;
use App\Actions\IssueVerificationCode;
use App\Enums\VerificationPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmEmailRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class EmailVerificationController extends Controller
{
    /**
     * Email a new code to verify the email, unless it is verified already (204).
     */
    public function store(Request $request, IssueVerificationCode $issueVerificationCode): Response
    {
        $user = $request->user();

        if ($user->email_verified_at !== null) {
            return response()->noContent();
        }

        $issueVerificationCode->handle($user, VerificationPurpose::EmailVerification);

        return response()->noContent(202);
    }

    /**
     * Verify the email with the code that was emailed to it.
     */
    public function update(ConfirmEmailRequest $request, ConfirmEmail $confirmEmail): UserResource
    {
        $user = $request->user();

        if (! $confirmEmail->handle($user, $request->validated('code'))) {
            throw ValidationException::withMessages(['code' => __('api.validation.verification_code')]);
        }

        return new UserResource($user);
    }
}
