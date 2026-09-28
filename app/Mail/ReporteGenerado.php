<?php

namespace App\Mail;

use App\Models\Reporte;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReporteGenerado extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Reporte $reporte,
        public string $pdf,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reporte: {$this->reporte->nombre}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.reporte-generado',
            with: ['reporte' => $this->reporte],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdf, "{$this->reporte->nombre}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
