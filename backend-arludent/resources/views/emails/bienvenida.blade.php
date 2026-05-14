<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a Arludent</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Bienvenido a Arludent!</h1>
        </div>

        <div class="content">
            <p>Hola <strong>{{ $usuario->username ?? $usuario->correo }}</strong>,</p>

            <p>Tu cuenta ha sido verificada exitosamente. Ya puedes iniciar sesión y disfrutar de todos nuestros servicios.</p>

            <p>Estamos aquí para cuidar de tu salud dental con la mejor atención y tecnología.</p>

            <p>Saludos,<br>El equipo de Arludent</p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Arludent. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
