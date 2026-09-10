# SaaS Architecture

Teamora is a modern multi-tenant SaaS application designed for sports organizations.

## Core Tech Stack
- **Language:** PHP 8.2+
- **Database:** MariaDB 10.4+ / MySQL 8.0+
- **Framework:** Custom MVC framework built on top of robust, secure routing (`nikic/fast-route`) and templating (`league/plates`).
- **Server:** Apache (XAMPP for dev, cPanel for prod)

## Application Layers
1. **Routing:** `FastRoute` handles URL dispatching, resolving controller actions.
2. **Middleware:** 
   - `SessionMiddleware`
   - `CsrfMiddleware`
   - `AuthMiddleware`
   - `TenantMiddleware`
3. **Controllers:** HTTP layer. Validates input, coordinates services, returns responses/views.
4. **Services:** Core business logic (e.g., `AuthService`, `BillingService`).
5. **Repositories / Models:** Database interaction layer (PDO).

## Scalability & Hosting
Designed to run efficiently on shared hosting (cPanel/Truehost) for MVP, while maintaining clean separation of concerns to allow future migration to VPS/Cloud (Docker/Kubernetes). Heavy background jobs will use cron-driven queues.
