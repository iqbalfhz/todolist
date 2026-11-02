<?php

namespace App\Mail;

use App\Enums\VerificationPurpose;
use App\Models\VerificationCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The plain text email with a code to reset the password or verify the email.
 */
class VerificationCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * It is queued once the transaction that saved the code commits, so
     * nobody receives a code that was never stored.
     */
    public function __construct(public VerificationPurpose $purpose, public string $code)
    {
        $this->afterCommit();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(subject: __("mail.{$this->purpose->value}.subject"));
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(text: 'mail.verification-code', with: [
            'intro' => __("mail.{$this->purpose->value}.intro", ['code' => $this->code]),
            'outro' => __("mail.{$this->purpose->value}.outro", ['minutes' => VerificationCode::VALID_FOR_MINUTES]),
        ]);
    }
}
