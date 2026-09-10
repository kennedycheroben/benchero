# Benchero Deployment Guide

## Production Architecture

- **GitHub Repository**: `kennedycheroben/benchero`
- **cPanel Git Working Tree**: `/repositories/benchero`
- **Production Domain**: `https://benchero.co.ke`
- **Document Root**: `/repositories/benchero/public`

## Intended Deployment Process

1. **Developer changes code locally.**
2. **Run tests:** Ensure the application works locally.
3. **`git status`:** Check for any uncommitted modifications.
4. **`git commit`:** Commit your clean changes with a descriptive message.
5. **`git push`:** Push changes to the GitHub repository (`main` branch).
6. **cPanel pulls the new commit:** In cPanel's Git Version Control or via SSH, pull the latest commit into `/repositories/benchero`.
7. **Install/update Composer dependencies if required:** Run `composer install --no-dev --optimize-autoloader`.
8. **Run only approved migrations:** Execute necessary database migrations. Do NOT run destructive migrations on production.
9. **Verify `.env` remains server-side:** Ensure the `.env` file on the production server is intact and has not been overwritten by tracked files.
10. **Clear/rebuild application cache if required:** Ensure the application is reading the latest templates and configurations.
11. **Test `https://benchero.co.ke`:** Validate the live site is functioning as expected.
12. **Check application logs:** Review `storage/logs/` (or server error logs) for any immediate issues.

## Rollback Procedure

If a deployment fails or causes critical errors on production, rollback using Git:

1. Identify the previous stable commit hash using `git log`.
2. In the cPanel repository (`/repositories/benchero`), checkout the stable commit: `git checkout <commit-hash>`.
3. If necessary, revert database migrations manually (only if safe to do so).
4. Run `composer install --no-dev --optimize-autoloader` to sync dependencies with the rolled-back commit.
5. Test the application to ensure stability.
6. Once stable, fix the issue locally, commit, and push a new forward-moving fix.
