# Docker Setup for HPR

This project uses **Laravel Sail**, which provides a lightweight command-line interface for interacting with Docker's development environment.

## Configuration
The docker configuration is defined in:
- `docker-compose.yml` (or `compose.yaml`) in the project root.
- `docker/` directory in the project root (contains customizations if any).

## Services
The default setup includes:
- **laravel.test**: The PHP 8.2 application container (Nginx + PHP-FPM).
- **mysql**: MySQL 8.0 database.
- **redis**: Redis for caching/queues.
- **mailpit/mailhog**: (Optional) For email testing.

## Useful Commands
Everything is managed via `./vendor/bin/sail`:

```bash
# Start containers (background)
./vendor/bin/sail up -d

# Stop containers
./vendor/bin/sail down

# Restart containers
./vendor/bin/sail restart

# View logs
./vendor/bin/sail logs -f

# Access container shell
./vendor/bin/sail shell
```

## Troubleshooting
If ports are occupied (e.g., local MySQL on 3306), Sail will fail.
Ensure no local services are blocking ports 80, 3306, 6379, etc.
Or modify `.env` ports:
```
APP_PORT=8000
FORWARD_DB_PORT=3307
```
