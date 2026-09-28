<?php

namespace App\Mail;

use App\Models\EventoTutorias;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventoUrlCompartida extends Mailable
{
    use SerializesModels;

    public function __construct(
        public EventoTutorias $evento,
        public string $tipo,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        $etiqueta = $this->tipo === 'entrada' ? 'Entrada' : 'Salida';

        return new Envelope(
            subject: "URL de {$etiqueta}: {$this->evento->nombre}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.evento-url-compartida',
            with: [
                'evento' => $this->evento,
                'tipo' => $this->tipo,
                'url' => $this->url,
            ],
        );
    }
}
