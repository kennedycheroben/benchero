# Deployment Guide

## Target Environment
- **Hosting:** Truehost / cPanel shared hosting (initial MVP target).
- **Web Server:** Apache 2.4.
- **PHP:** 8.2+.
- **Database:** MySQL 8.0+ / MariaDB 10.4+.

## Deployment Steps (Manual / Scripted)
1. **Source Code:** Ensure the document root points *only* to the `/public` directory. The `app`, `core`, and `.env` files must be outside the public web root.
2. **Environment Configuration:** 
   - Copy `.env.example` to `.env`.
   - Update database credentials.
   - Generate `APP_KEY`.
   - Configure SMTP for the production mailer.
   - Set `APP_DEBUG=false` and `APP_ENV=production`.
3. **Database:** Execute `php bin/migrate.php`.
4. **Dependencies:** `composer install --no-dev --optimize-autoloader`.
5. **Permissions:** Ensure `storage/` directories are writable by the web server user.

## Future (Phase 10)
- Automated deployment via CI/CD (GitHub Actions) utilizing rsync or atomic symlink deployments (e.g., Deployer).
