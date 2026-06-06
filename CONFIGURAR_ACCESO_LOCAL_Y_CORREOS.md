# Configuración para Acceso en Red Local y Correos Electrónicos

Este documento explica paso a paso cómo hacer que tu proyecto Arludent funcione en distintas computadoras dentro de tu misma red local (Wi-Fi o cable), y cómo verificar que los enlaces de los correos apunten a tu máquina.

## ¿Qué está ocurriendo?
Actualmente, los correos llegan con enlaces que dicen `http://localhost:3000/...`.
La palabra **`localhost`** significa "mi propia computadora". Cuando tu profesor abre el correo en su PC y hace clic, su computadora intenta buscar la página de Arludent dentro de su propio sistema y obviamente falla porque el sistema lo tienes instalado tú.

Para solucionarlo, debemos usar la **Dirección IP** de tu computadora para que la máquina del profesor sepa cómo llegar a la tuya.

---

## PASO 1: Descubrir tu Dirección IP
1. En tu máquina (donde corre Docker con el sistema), abre **PowerShell** o el **Símbolo del sistema (CMD)**.
2. Escribe el comando:
   ```cmd
   ipconfig
   ```
3. Busca el adaptador que estés usando (Wi-Fi o Ethernet) y anota la **Dirección IPv4**.
   * Ejemplo: `192.168.1.15`

---

## PASO 2: Configurar el archivo `.env`
He revisado el código fuente de tu aplicación: el sistema de correos de verificación y recuperación de contraseñas de Laravel usa la variable `frontend_url` en su configuración, la cual extrae su valor de `FRONTEND_URL` en tu archivo `.env`.

Abre el archivo **`.env`** en la carpeta principal `ArludentDeploy` y edita la sección "URLs PÚBLICAS":

**ANTES:**
```ini
APP_URL=http://localhost:8080
FRONTEND_URL=http://localhost:3000
```

**DESPUÉS** *(Cambia 192.168.1.15 por tu IP real anotada en el paso 1)*:
```ini
APP_URL=http://192.168.1.15:8080
FRONTEND_URL=http://192.168.1.15:3000
```

---

## PASO 3: (Opcional) Verificar configuración de envíos de correos
Mencionaste que podrías haber olvidado algo sobre contraseñas o configuración de correos. En tu archivo `.env` todo parece estar bien para Gmail:
```ini
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=erickhc1331@gmail.com
MAIL_PASSWORD=strygejlsivnuxze
MAIL_FROM_ADDRESS=erickhc1331@gmail.com
```
*Tip importante:* Esa contraseña que tienes (`strygejlsivnuxze`) es una "Contraseña de aplicación" de Google. Si el sistema te da error al enviar correos (por ejemplo código 535 de autenticación), significa que esa contraseña de aplicación expiró o fue eliminada, y tendrías que generar una nueva en la **Configuración de Seguridad de tu Cuenta de Google**. Si ya te están llegando los correos correctamente, omite esto.

---

## PASO 4: Aplicar los cambios en Docker y Limpiar la Caché
Este es el paso donde la mayoría falla. Si solo cambias el `.env`, el backend (Laravel) no se entera de inmediato porque guarda todo en caché.

Abre la terminal en la carpeta de tu proyecto y ejecuta lo siguiente (uno por uno):

**1. Apagar el sistema:**
```bash
docker-compose down
```

**2. Volver a arrancar aplicando las nuevas variables:**
```bash
docker-compose up -d
```

**3. Limpiar la caché de configuración del Backend (Obligatorio para que impacte los correos):**
```bash
docker-compose exec backend php artisan config:clear
```

---

## PASO 5: Prueba de Fuego 🔥
1. Pídele al profesor que, desde su propia computadora, abra el navegador.
2. Deberá ingresar `http://TU_IP:3000` (Ejemplo: `http://192.168.1.15:3000`). ¡La página de inicio de Arludent debería cargarle!
3. Haz que se registre un usuario nuevo o envía una confirmación desde su computadora.
4. Cuando le llegue el correo, **el enlace ahora será `http://192.168.1.15:3000/email/verify?...`**.
5. Al darle clic, ¡todo funcionará sin problemas!

> **NOTA DE RED:** Para que todo esto funcione, tanto tu computadora como la computadora evaluadora del profesor deben imperativamente estar conectadas a la misma red Wi-Fi o router (Red local).