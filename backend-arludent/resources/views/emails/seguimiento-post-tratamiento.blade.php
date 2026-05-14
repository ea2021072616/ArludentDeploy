<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento Post-Tratamiento</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.95;
        }
        .content {
            padding: 40px 30px;
            color: #333333;
            line-height: 1.6;
        }
        .greeting {
            font-size: 18px;
            color: #667eea;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .message {
            font-size: 15px;
            margin-bottom: 25px;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        .info-box strong {
            color: #667eea;
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-box p {
            margin: 5px 0;
            color: #555;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 40px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            font-size: 13px;
            color: #666;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            margin: 5px 0;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #e9ecef, transparent);
            margin: 25px 0;
        }
        .emoji {
            font-size: 48px;
            text-align: center;
            margin: 20px 0;
        }
        .note {
            font-size: 13px;
            color: #6c757d;
            font-style: italic;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed #dee2e6;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>🦷 Clínica Arludent</h1>
            <p>Cuidando tu sonrisa con tecnología e inteligencia</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                ¡Hola {{ $seguimiento->paciente->nombre }}!
            </div>

            <div class="emoji">
                😊
            </div>

            <div class="message">
                <p>Esperamos que te encuentres muy bien después de tu reciente visita a nuestra clínica.</p>

                <p>Como parte de nuestro compromiso con tu salud dental, nos gustaría saber cómo te sientes después de tu tratamiento. Tu opinión es muy importante para nosotros.</p>
            </div>

            <div class="info-box">
                <strong>📋 Detalles de tu tratamiento</strong>
                @if($seguimiento->cita)
                    <p><strong>Fecha de la cita:</strong> {{ $seguimiento->cita->fecha->format('d/m/Y H:i') }}</p>
                @endif
                <p><strong>Tipo de seguimiento:</strong> {{ ucfirst($seguimiento->tipo) }}</p>
                @if($seguimiento->notas)
                    <p><strong>Observaciones:</strong> {{ $seguimiento->notas }}</p>
                @endif
            </div>

            <div class="divider"></div>

            <p style="font-size: 16px; font-weight: 600; color: #333; margin-bottom: 15px;">
                Por favor, tómate un momento para responder estas preguntas:
            </p>

            <ul style="color: #555; margin-left: 20px;">
                <li>¿Cómo te sientes después del tratamiento?</li>
                <li>¿Has experimentado alguna molestia o síntoma inusual?</li>
                <li>¿Necesitas agendar una cita de revisión?</li>
            </ul>

            <div class="button-container">
                <a href="{{ $enlaceRespuesta }}" class="cta-button">
                    Responder Cuestionario
                </a>
            </div>

            <div class="note">
                <strong>💡 Nota importante:</strong> Este enlace es personal e intransferible. Nuestro sistema de inteligencia artificial analizará tus respuestas de forma inmediata. Si detectamos alguna urgencia, un miembro de nuestro equipo se pondrá en contacto contigo rápidamente.
            </div>

            <div class="divider"></div>

            <p style="text-align: center; color: #667eea; font-weight: 600;">
                ¡Gracias por confiar en Arludent! 💙
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Clínica Arludent</strong></p>
            <p>📍 [Tu dirección aquí]</p>
            <p>📞 [Tu teléfono aquí] | 📧 <a href="mailto:contacto@arludent.com">contacto@arludent.com</a></p>
            <p style="margin-top: 15px; font-size: 12px; color: #999;">
                Si no solicitaste este correo, puedes ignorarlo de forma segura.
            </p>
        </div>
    </div>
</body>
</html>
