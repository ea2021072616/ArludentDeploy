CLÍNICA ARLUDENT
================

¡Hola {{ $seguimiento->paciente->nombre }}!

Esperamos que te encuentres muy bien después de tu reciente visita a nuestra clínica.

Como parte de nuestro compromiso con tu salud dental, nos gustaría saber cómo te sientes después de tu tratamiento.

DETALLES DE TU TRATAMIENTO
---------------------------
@if($seguimiento->cita)
Fecha de la cita: {{ $seguimiento->cita->fecha->format('d/m/Y H:i') }}
@endif
Tipo de seguimiento: {{ ucfirst($seguimiento->tipo) }}
@if($seguimiento->notas)
Observaciones: {{ $seguimiento->notas }}
@endif

RESPONDER CUESTIONARIO
----------------------
Por favor, accede al siguiente enlace para responder algunas preguntas sobre tu estado:

{{ $enlaceRespuesta }}

Preguntas que te haremos:
- ¿Cómo te sientes después del tratamiento?
- ¿Has experimentado alguna molestia o síntoma inusual?
- ¿Necesitas agendar una cita de revisión?

NOTA IMPORTANTE: Este enlace es personal e intransferible. Nuestro sistema de inteligencia artificial analizará tus respuestas de forma inmediata. Si detectamos alguna urgencia, un miembro de nuestro equipo se pondrá en contacto contigo rápidamente.

¡Gracias por confiar en Arludent!

---------------------------
Clínica Arludent
[Tu dirección aquí]
Teléfono: [Tu teléfono aquí]
Email: contacto@arludent.com

Si no solicitaste este correo, puedes ignorarlo de forma segura.
