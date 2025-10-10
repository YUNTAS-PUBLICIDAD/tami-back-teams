<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductInfoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $viewName;

    public function __construct($data, $viewName)
    {
        $this->data = $data;
        $this->viewName = $viewName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->data['name'] . ', empecemos a crear juntos ✨',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->viewName,
            with: [
                'product' => $this->data['product'] ?? [],
                'data' => $this->data,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
