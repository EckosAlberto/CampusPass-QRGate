<?php

namespace App\Mail;

use App\Models\ReporteGraduacion;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReporteGraduacionGenerado extends Mailable
{
    use SerializesModels;

    public function __construct(
        public ReporteGraduacion $reporte,
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
            markdown: 'mail.reporte-graduacion-generado',
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
