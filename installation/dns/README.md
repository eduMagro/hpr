# DNS & Local Domain Configuration

The application is configured to correspond to `APP_URL=http://app.test`.
To make this URL work locally, we map it to `127.0.0.1`.

## Automatic Configuration
The `setup.sh` script attempts to add the following line to your `/etc/hosts` file:
```
127.0.0.1 app.test
```

## Manual Configuration
If you need to do this manually:
1. Open the hosts file:
   ```bash
   sudo nano /etc/hosts
   ```
2. Add the line:
   ```
   127.0.0.1 app.test
   ```
3. Save and exit (Ctrl+O, Enter, Ctrl+X).

## Access
Once configured and Docker is running (`./vendor/bin/sail up`), you can access the app at:
- http://app.test

Required `.env` setting:
```ini
APP_URL=http://app.test
```
