# Route Map

## Global / Public Routes
- `GET /` - Public landing page

## Authentication Routes
- `GET /register`, `POST /register` - Registration
- `GET /login`, `POST /login` - Login
- `POST /logout` - Logout (requires auth)
- `GET /verify-email/{id}/{hash}` - Email verification
- `GET /password/forgot`, `POST /password/forgot` - Request reset
- `GET /password/reset/{token}`, `POST /password/reset` - Perform reset

## Platform Admin Routes
- `GET /admin/*` - Platform administration (Requires `platform_admin` role)

## Tenant Routes (Requires Authentication + Tenant Context)
**Prefix:** `/o/{tenant_slug}`
- `GET /` - Dashboard
- `GET /setup` - Onboarding / setup view
- `GET /teams`, `POST /teams` - Team management
- `GET /players`, `POST /players` - Player management
- `GET /staff`, `POST /staff` - Staff management
- `GET /billing` - Organization billing and subscriptions
