<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendEmailForgotPasswordCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public $user, public $code, public $formattedDate, public $formattedTime)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recuperar de senha de acesso',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'recoverpassword.sendEmailHtmlForgotPasswordCode',
            text: 'recoverpassword.sendEmailTextForgotPasswordCode',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
