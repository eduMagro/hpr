# Solución de Problemas (Troubleshooting)

## Error: `failed to resolve reference "sail-8.4/app:latest"`
Si al ejecutar `sail up` aparece este error, significa que Docker intenta descargar la imagen en lugar de construirla.
**Solución**: Ejecutar `sail build` antes de `sail up`.
El script `setup.sh` ya incluye este paso.

## Permisos de Docker
Si aparece "permission denied" al conectar con el socket de Docker daemon:
1. Asegúrate de ejecutar el script con `sudo`.
2. El usuario debe estar en el grupo `docker` (el script lo añade).
3. Puede requerir cerrar sesión y volver a entrar (o reiniciar) para que los grupos se actualicen completamente.

## Puertos ocupados
Error: `Bind for 0.0.0.0:80 failed: port is already allocated`
**Solución**:
- Verificar si Apache/Nginx están corriendo en el host: `sudo systemctl stop apache2` / `sudo systemctl stop nginx`.
- O cambiar los puertos en `.env`: `APP_PORT=8000`.

## Base de Datos
Si la conexión falla al inicio:
La base de datos tarda unos segundos en arrancar. El script espera 10 segundos, pero si el PC es lento, puede requerir más.
Ejecutar manualmente:
```bash
./vendor/bin/sail artisan migrate
```
cuando la base de datos esté lista.
