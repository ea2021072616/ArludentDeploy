<?php

namespace App\Mail;

use App\Models\SeguimientoPostTratamiento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class SeguimientoPostTratamientoMail extends Mailable
{
    use Queueable, SerializesModels;

    public SeguimientoPostTratamiento $seguimiento;
    public string $enlaceRespuesta;

    /**
     * Create a new message instance.
     */
    public function __construct(SeguimientoPostTratamiento $seguimiento)
    {
        $this->seguimiento = $seguimiento;
        $this->enlaceRespuesta = url('/seguimiento/' . $seguimiento->token_respuesta);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address', 'clinica@arludent.com'),
                config('mail.from.name', 'Clínica Arludent')
            ),
            subject: '¿Cómo te sientes después de tu tratamiento?',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.seguimiento-post-tratamiento',
            text: 'emails.seguimiento-post-tratamiento-text',
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
