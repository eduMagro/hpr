# Environment Variables (.env)

This project relies on environment variables for configuration.
A copy of your current `.env` has been backed up to `installation/env_backup` (WARNING: Contains secrets).
For a fresh install, `setup.sh` copies `.env.example` to `.env`.

## Critical Variables

### Database (Local / Sail)
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=manager_pruebas
DB_USERNAME=root
DB_PASSWORD=
```

### Ferrawin (External SQL Server)
Used for syncing with the legacy system.
```ini
FERRAWIN_DB_HOST=...
FERRAWIN_DB_PORT=1433
FERRAWIN_DB_DATABASE=FERRAWIN
FERRAWIN_DB_USERNAME=sa
FERRAWIN_DB_PASSWORD=...
FERRAWIN_API_TOKEN=...
```

### 3rd Party Services
- **OpenAI / Claude / Gemini**: API Keys for AI features.
- **Firebase**: For push notifications/auth.
- **Pusher**: For real-time events.
- **AWS**: For file storage (if enabled).
- **Docupipe**: For PDF scanning services.

## Application Settings
- `APP_URL`: The URL of the app (e.g., http://localhost or http://app.test).
- `APP_ENV`: local or production.
- `APP_DEBUG`: true for development.
