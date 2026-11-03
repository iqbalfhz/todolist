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
 * The email with a code to reset the password or verify the email, in HTML
 * with a plain text version for mail apps that do not show HTML.
 */
class VerificationCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The name of the account the code is for.
     *
     * Declared with a default rather than promoted, so an email queued
     * before this field existed is still sent, with a greeting without a name.
     */
    public string $recipientName = '';

    /**
     * Create a new message instance.
     *
     * It is queued once the transaction that saved the code commits, so
     * nobody receives a code that was never stored.
     */
    public function __construct(public VerificationPurpose $purpose, public string $code, string $recipientName = '')
    {
        $this->recipientName = $recipientName;

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
        $supportEmail = config('admin.support_email');

        return new Content(
            view: 'mail.verification-code',
            text: 'mail.verification-code-text',
            with: [
                'subjectLine' => __("mail.{$this->purpose->value}.subject"),
                'greeting' => $this->recipientName === ''
                    ? __('mail.greeting_without_name')
                    : __('mail.greeting', ['name' => $this->recipientName]),
                'intro' => __("mail.{$this->purpose->value}.intro"),
                'validity' => __('mail.validity', ['minutes' => VerificationCode::VALID_FOR_MINUTES]),
                'outro' => __("mail.{$this->purpose->value}.outro"),
                'closing' => __('mail.closing'),
                'signature' => __('mail.signature'),
                'automated' => __('mail.automated'),
                'supportEmail' => filled($supportEmail) ? $supportEmail : null,
            ],
        );
    }
}
