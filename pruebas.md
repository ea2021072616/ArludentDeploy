# Guía para la Demostración de Pruebas Unitarias (Sustentación)

Esta guía te servirá como "acordeón" o libreto para mostrarle a tu profesor que tus pruebas unitarias realmente interactúan con la base de datos a través de la API, insertando y modificando registros reales.

---

## 🛠️ El Truco Principal: Desactivar la Limpieza Automática

Por defecto, las pruebas en Laravel usan el trait `RefreshDatabase`, el cual crea una transacción en memoria y la revierte al final de cada prueba. Esto evita que la base de datos se llene de basura, pero hace imposible mostrarle los datos al profesor.

**Para la demostración:**
1. Abre el archivo de tu prueba (ej. `CU01_GestionarUsuariosTest.php`).
2. Ve a la línea donde dice `use RefreshDatabase;` (usualmente al inicio de la clase).
3. Ponle un comentario para desactivarlo temporalmente:
   ```php
   // use RefreshDatabase;
   ```

> **NOTA IMPORTANTE:** Como desactivaste la limpieza, **SOLO PUEDES CORRER UNA PRUEBA A LA VEZ**. Si corres todo el archivo de golpe, los datos de los `setUp()` chocarán entre sí por la restricción de correos o DNIs duplicados.

---

## 🚀 El Flujo de Demostración (Paso a Paso)

Este es el ciclo que debes repetir cada vez que le vayas a mostrar una prueba diferente a tu profesor.

### Paso 1: Limpiar la Base de Datos (Obligatorio)
Antes de correr cualquier prueba, asegúrate de que la base de datos de testing esté totalmente limpia y en blanco para evitar choques de datos duplicados de pruebas anteriores.

Ve a la terminal y ejecuta:
```bash
php artisan migrate:fresh --env=testing
```
*Muestra HeidiSQL (presionando F5) para que el profe vea que las tablas están vacías.*

### Paso 2: Ejecutar una Prueba Específica
Aquí tienes varias opciones dependiendo de lo que el profesor te pida ver. Copia y pega uno de estos comandos en la terminal:

**Opción A: Crear un Paciente Real**
(Crea un usuario, lo asocia a un rol y le genera su perfil completo de Paciente).
```bash
php artisan test --filter admin_puede_crear_usuario_con_rol_paciente_y_perfil
```
*-> Ve a HeidiSQL y muestra la tabla `usuarios` y `pacientes`.*

**Opción B: Crear un Médico Real**
(Crea un usuario, le asigna rol y le genera su perfil de Médico con Nro de Colegiatura).
```bash
php artisan test --filter crear_usuario_nuevo_con_datos_de_perfil_validos
```
*-> Ve a HeidiSQL y muestra la tabla `usuarios` y `medicos`.*

**Opción C: Editar los datos de un Usuario (Update)**
(Comprueba que el sistema puede alterar un registro ya existente).
```bash
php artisan test --filter admin_edita_rol_de_un_usuario_existente
```
*-> Ve a HeidiSQL y muestra cómo el usuario cambió de datos/rol.*

### Paso 3: Limpiar Todo al Finalizar
Cuando hayas terminado la sustentación y tu profesor esté feliz, **NO OLVIDES**:
1. Volver a tu archivo de código (`CU01_GestionarUsuariosTest.php`).
2. Quitar el comentario a la línea para que quede así:
   ```php
   use RefreshDatabase;
   ```
3. Volver a limpiar tu BD por última vez con `php artisan migrate:fresh --env=testing`.

---

## 🖥️ Pruebas E2E (Frontend) con Cypress

Para demostrarle a tu profesor que el sistema funciona a nivel visual (como si fueras un usuario real haciendo clics y escribiendo en la pantalla), debes abrir la interfaz gráfica de Cypress.

### Paso 1: Levantar los servidores
Asegúrate de tener corriendo tu backend y tu frontend (vite) en terminales diferentes como lo haces normalmente.

### Paso 2: Abrir Cypress
Ve a la consola de comandos, asegúrate de estar dentro de la carpeta de tu frontend (`arludent-frontend`) y ejecuta:
```bash
npx cypress open
```

### Paso 3: Ejecutar la prueba visualmente
1. Se abrirá la ventana de Cypress.
2. Haz clic en **E2E Testing**.
3. Selecciona tu navegador (ej. Chrome) y haz clic en **Start E2E Testing**.
4. Verás la lista de tus archivos de pruebas (ej. `CU13_AuditoriaSistema.cy.ts`).
5. ¡Haz clic en cualquiera de ellos! 

Cypress abrirá un navegador automático y verás a un "robot" escribiendo, haciendo clics y navegando por tu sistema a toda velocidad frente a los ojos del profesor. ¡Esto siempre da muchísimos puntos en una sustentación!
