<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountLockedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $unlockUrl;

    public function __construct(public User $user, string $token)
    {
        // Built from APP_URL on purpose, not from the request host.
        $this->unlockUrl = rtrim(config('app.url'), '/') . '/unlock?token=' . $token;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Security Alert: Your ' . config('app.name') . ' account has been locked',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-locked',
            text: 'emails.account-locked-text',
        );
    }
}