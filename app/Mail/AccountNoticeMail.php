<?php

namespace App\Mail;

use App\Enums\AccountNotice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The email that tells the owner the email or password of the account was
 * changed, in HTML with a plain text version, in the look of the code emails.
 */
class AccountNoticeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * The new email arrives masked, so the queued job never holds it whole.
     * It is queued once the transaction that made the change commits.
     */
    public function __construct(public AccountNotice $notice, public string $recipientName, public ?string $maskedEmail = null)
    {
        $this->afterCommit();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(subject: __("mail.{$this->notice->value}.subject"));
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $supportEmail = config('admin.support_email');
        $supportEmail = filled($supportEmail) ? $supportEmail : null;

        return new Content(
            view: 'mail.account-notice',
            text: 'mail.account-notice-text',
            with: [
                'subjectLine' => __("mail.{$this->notice->value}.subject"),
                'greeting' => $this->recipientName === ''
                    ? __('mail.greeting_without_name')
                    : __('mail.greeting', ['name' => $this->recipientName]),
                'body' => __("mail.{$this->notice->value}.body", ['email' => $this->maskedEmail]),
                'warningKey' => $this->warningKey($supportEmail),
                'closing' => __('mail.closing'),
                'signature' => __('mail.signature'),
                'automated' => __('mail.automated'),
                'supportEmail' => $supportEmail,
            ],
        );
    }

    /**
     * Get the key of the sentence that says what to do when the owner did not make the change.
     *
     * Without a support address, the email change notice leaves it out, and
     * the password notice keeps only the advice to reset the password.
     */
    private function warningKey(?string $supportEmail): ?string
    {
        if ($supportEmail !== null) {
            return "mail.{$this->notice->value}.warning";
        }

        return $this->notice === AccountNotice::PasswordChanged ? 'mail.password_changed.warning_without_support' : null;
    }
}
