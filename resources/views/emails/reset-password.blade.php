<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Recuperación de Contraseña</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #2c3e50;">Hola, {{ $user->name }}</h2>

    <p>Recibimos una solicitud para restablecer tu contraseña en TiendaPOS.</p>

    <p>Tu código de verificación es:</p>

    <div style="background-color: #f0f0f0; padding: 15px; text-align: center; border-radius: 8px; margin: 20px 0;">
        <span style="font-size: 28px; font-weight: bold; letter-spacing: 5px; color: #2563eb;">{{ $token }}</span>
    </div>

    <p>O haz clic en el siguiente botón para restablecerla:</p>

    <p style="text-align: center; margin: 30px 0;">
        <a href="{{ $resetUrl }}"
           style="background-color: #2563eb; color: white; padding: 12px 30px;
                  text-decoration: none; border-radius: 6px; font-weight: bold;">
            Restablecer Contraseña
        </a>
    </p>

    <p style="color: #7f8c8d; font-size: 12px;">
        Si no solicitaste este cambio, ignora este email.<br>
        El código expira en 60 minutos.<br><br>
        — Equipo TiendaPOS
    </p>
</body>
</html>
