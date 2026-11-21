<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** رسالة للمتدرب بهوية المعهد (نص الرسالة الثابتة نفسه المستخدم في واتساب) */
class TraineeMessage extends Mailable
{
    public function __construct(public string $subjectLine, public string $body)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.trainee', text: 'emails.trainee-text');
    }
}
