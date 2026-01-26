# Sitio Normalismo (primera versión)

Este proyecto implementa una primera versión funcional del portal del Normalismo con:

- Acceso por credenciales (tabla `usuarios`)
- Restricción por Escuela Normal (CCT asociado al usuario)
- Módulo de alumnos con registro de **Actividades académicas**
- Video de fondo (`assets/video/fondo.mp4`)
- Interfaz moderna con Bootstrap 5 y estilos con paleta institucional

## Requisitos

- PHP 8.4+
- MySQL 8.0+ (BD: `enpem_normalismo`)
- Extensiones PHP: mysqli, session, openssl

## Configuración de conexión

Defina variables de entorno en su servidor (recomendado):

- `DB_HOST`
- `DB_NAME` (por defecto `enpem_normalismo`)
- `DB_USER`
- `DB_PASS`

## Despliegue rápido (carpeta pública)

Si su hosting utiliza `public_html`, copie el contenido de este proyecto al directorio público.
La ruta `/` carga `index.php`.

## Seguridad incluida

- Consultas preparadas (mitiga SQL Injection).
- Escape de salidas (mitiga XSS).
- Token CSRF en operaciones POST.

## Notas sobre contraseñas

La tabla `usuarios.passwd` está definida como `varchar(15)`. Para producción se recomienda ampliar
a `varchar(255)` y almacenar hashes con `password_hash()`.



## Carpeta /normalismo (multi-proyecto)

El sistema está preparado para ejecutarse dentro de una carpeta (por defecto: `/normalismo`).

- Si lo instala en `/normalismo`, no requiere cambios.
- Si lo instala en otra carpeta (ej. `/normalismo2026`), defina la variable de entorno:

```
APP_BASE_URL=/normalismo
```

> Nota: el valor no debe terminar con `/`.
