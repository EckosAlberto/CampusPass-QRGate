<?php

namespace App\Mail;

use App\Models\CeremoniaGraduacion;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CeremoniaUrlCompartida extends Mailable
{
    use SerializesModels;

    public function __construct(
        public CeremoniaGraduacion $ceremonia,
        public string $tipo,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        $etiqueta = $this->tipo === 'registro' ? 'Registrar Acceso' : 'Enlace remoto';

        return new Envelope(
            subject: "URL de {$etiqueta}: {$this->ceremonia->nombre}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.ceremonia-url-compartida',
            with: [
                'ceremonia' => $this->ceremonia,
                'tipo' => $this->tipo,
                'url' => $this->url,
            ],
        );
    }
}
