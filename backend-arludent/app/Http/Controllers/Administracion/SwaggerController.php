<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     title="API Arludent - Sistema de Gestión Odontológica",
 *     version="1.0.0",
 *     description="API RESTful completa para gestión de consultorios odontológicos con autenticación JWT, MFA y módulos clínicos",
 *     @OA\Contact(
 *         email="soporte@arludent.com",
 *         name="Soporte Técnico Arludent"
 *     ),
 *     @OA\License(
 *         name="Propietario",
 *         url="https://arludent.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Servidor de Desarrollo Local"
 * )
 *
 * @OA\Server(
 *     url="https://api.arludent.com",
 *     description="Servidor de Producción"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearer",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Autenticación mediante token JWT. Formato: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="Autenticación",
 *     description="Endpoints de registro, login, verificación de correo, MFA y recuperación de contraseña"
 * )
 *
 * @OA\Tag(
 *     name="Usuarios",
 *     description="Gestión de perfiles y datos de usuario"
 * )
 *
 * @OA\Tag(
 *     name="Pacientes",
 *     description="Gestión de pacientes del consultorio"
 * )
 *
 * @OA\Tag(
 *     name="Médicos",
 *     description="Gestión de médicos y odontólogos"
 * )
 *
 * @OA\Tag(
 *     name="Citas",
 *     description="Gestión de citas médicas y disponibilidad"
 * )
 *
 * @OA\Tag(
 *     name="Historial Clínico",
 *     description="Gestión de historiales clínicos y tratamientos"
 * )
 *
 * @OA\Tag(
 *     name="Odontograma",
 *     description="Gestión de odontogramas digitales"
 * )
 *
 * @OA\Tag(
 *     name="Tratamientos",
 *     description="Catálogo y registro de tratamientos odontológicos"
 * )
 *
 * @OA\Tag(
 *     name="Pagos",
 *     description="Gestión de pagos y facturación"
 * )
 *
 * @OA\Tag(
 *     name="Sistema",
 *     description="Logs, auditoría y configuración del sistema"
 * )
 */
class SwaggerController extends Controller
{
    // Este controlador solo sirve para las anotaciones de Swagger
    // No tiene métodos, solo documentación
}
