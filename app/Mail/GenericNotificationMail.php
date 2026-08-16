<?php namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericNotificationMail extends Mailable {
    use Queueable, SerializesModels;
    public $subjectText; public $body;
    public function __construct($subject, $body) { $this->subjectText = $subject; $this->body = $body; }
    public function envelope(): Envelope { return new Envelope(subject: $this->subjectText); }
    public function content(): Content { return new Content(htmlString: nl2br(e($this->body))); }
}