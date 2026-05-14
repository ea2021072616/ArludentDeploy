<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña - Arludent</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .content {
            color: #555;
            line-height: 1.6;
        }
        .button {
            display: inline-block;
            padding: 15px 30px;
            background-color: #e74c3c;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
            text-align: center;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Recuperación de Contraseña</h1>
        </div>

        <div class="content">
            <p>Hola <strong>{{ $usuario->username ?? $usuario->correo }}</strong>,</p>

            <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en Arludent.</p>

            <div style="text-align: center;">
                <a href="{{ $url }}" class="button">Restablecer Contraseña</a>
            </div>

            <p>Si no puedes hacer clic en el botón, copia y pega el siguiente enlace en tu navegador:</p>
            <p style="word-break: break-all; color: #e74c3c;">{{ $url }}</p>

            <div class="warning">
                <p><strong>⚠️ Importante:</strong></p>
                <ul>
                    <li>Este enlace expirará en 60 minutos.</li>
                    <li>Si no solicitaste restablecer tu contraseña, ignora este correo.</li>
                    <li>Tu contraseña actual permanecerá sin cambios hasta que crees una nueva.</li>
                </ul>
            </div>

            <p>Saludos,<br>El equipo de Arludent</p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Arludent. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
