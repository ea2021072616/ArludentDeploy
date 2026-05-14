<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contraseña Actualizada - Arludent</title>
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
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
            text-align: center;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid: #c3e6cb;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Contraseña Actualizada</h1>
        </div>

        <div class="content">
            <p>Hola <strong>{{ $usuario->username ?? $usuario->correo }}</strong>,</p>

            <div class="success">
                <p><strong>✓ Tu contraseña ha sido actualizada exitosamente</strong></p>
            </div>

            <p>Si no realizaste este cambio, por favor contacta inmediatamente con nuestro equipo de soporte.</p>

            <p>Saludos,<br>El equipo de Arludent</p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Arludent. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
