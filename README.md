<p align="center">
    <img src="public/imagenes/logoHPR.png" alt="HPR Logo" width="400">
</p>

# HPR

Sistema de gestión para Hierros Paco Reyes.

## Guía Rápida

### Primer uso (Instalación desde cero/Formateo)
Si acabas de clonar el repositorio en un ordenador limpio (Ubuntu/Linux), ejecuta este script **una sola vez**. Se encargará de instalar Docker, PHP, configurar la base de datos y todo lo necesario.

```bash
sudo ./installation/setup.sh
```

### Uso diario (Arrancar el proyecto)
Cada vez que enciendas el ordenador y quieras trabajar, simplemente ejecuta:

```bash
./start.sh
```

Esto levantará los servicios y te dejará la web lista en:
- **Web:** [http://app.test](http://app.test)
- **Base de Datos:** [http://localhost:8080](http://localhost:8080)

### Parar el proyecto
Cuando termines de trabajar, puedes detener todo ejecutando:

```bash
./stop.sh
```

---

## Desarrollo

Para desarrollar con recarga en caliente (Hot Reload):
```bash
npm run dev
```

Para comandos de Laravel (Artisan), usa `sail`:
```bash
./vendor/bin/sail artisan [comando]
```
Ejemplo: `./vendor/bin/sail artisan migrate`

## Documentación Técnica
Todo lo referente a la instalación y configuración detallada está en la carpeta `/installation`.
- [Detalles de Instalación](installation/README.md)
- [Solución de Problemas](installation/TROUBLESHOOTING.md)