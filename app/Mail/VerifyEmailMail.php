<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $verifyUrl;

    public function __construct(public User $user, string $token)
    {
        // Built from APP_URL on purpose, not from the request host.
        $this->verifyUrl = rtrim(config('app.url'), '/') . '/verify-email?token=' . $token;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Action Required: Verify your email address for ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email',
            text: 'emails.verify-email-text',
        );
    }
}