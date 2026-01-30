# Normalismo - Fase 1 (Login)

## Requisitos
- PHP 8.x
- MySQL/MariaDB
- Servidor web (Apache/Nginx)
- Proyecto desplegado en subcarpeta: `/normalismo`

## Configuración
1. Ajusta credenciales en `config/db.php`.
2. Verifica que el proyecto se sirva desde:
   - `http://localhost/normalismo/`
3. Si cambiaste la subcarpeta, ajusta `BASE_URL` en `config/app.php`.

## Seguridad (importante)
Esta fase valida `usuarios.passwd` en **texto plano** (por solicitud).
Recomendación: migrar a `password_hash()` y `VARCHAR(255)` lo antes posible.
