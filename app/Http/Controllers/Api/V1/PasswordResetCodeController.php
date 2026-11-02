<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\IssueVerificationCode;
use App\Enums\VerificationPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use Illuminate\Http\Response;

class PasswordResetCodeController extends Controller
{
    /**
     * Email a code to reset the password, when the email belongs to an account.
     *
     * Answers 202 either way, so nobody can find out which emails have an account.
     */
    public function store(ForgotPasswordRequest $request, IssueVerificationCode $issueVerificationCode): Response
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user !== null) {
            $issueVerificationCode->handle($user, VerificationPurpose::PasswordReset);
        }

        return response()->noContent(202);
    }
}
