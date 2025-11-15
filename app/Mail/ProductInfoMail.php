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
    public $asunto;

    public function __construct($data, $viewName,$asunto)
    {
        $this->data = $data;
        $this->viewName = $viewName;
        $this->asunto = $asunto;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->asunto,
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
