# Database Configuration

The project uses **MySQL 8.0** running in a Docker container.

## Connection Details
Defined in `.env`:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1  # When connecting from host
# DB_HOST=mysql      # When connecting from inside other docker containers
DB_PORT=3306
DB_DATABASE=manager_pruebas  # Or as defined
DB_USERNAME=root
DB_PASSWORD=       # Usually empty for local/Sail, or 'password'
```

## Management
You can access the database via:
1. **CLI**:
   ```bash
   ./vendor/bin/sail mysql
   ```
2. **GUI Tools (TablePlus, DBeaver)**:
   Connect to `localhost:3306` with user `root` and the configured password.

## Migrations
To recreate the database structure:
```bash
./vendor/bin/sail artisan migrate
```

To reset and seed dummy data:
```bash
./vendor/bin/sail artisan migrate:fresh --seed
```
