<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyQuestionNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $date
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【Daily Vocabit】本日の問題が公開されました',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-question-notification',
        );
    }
}
