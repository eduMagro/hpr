# PHP Configuration

The project is built on **Laravel 10/11** and requires **PHP 8.2** or higher.

## System Requirements
If running locally (outside Docker), ensure you have:
- PHP >= 8.2
- BCMath PHP Extension
- Ctype PHP Extension
- Fileinfo PHP Extension
- JSON PHP Extension
- Mbstring PHP Extension
- OpenSSL PHP Extension
- PDO PHP Extension
- Tokenizer PHP Extension
- XML PHP Extension

The `setup.sh` script installs all these via the `ondrej/php` PPA.

## Composer
Dependencies are managed via `composer.json`.
To install dependencies:
```bash
composer install
```
To update dependencies:
```bash
composer update
```
