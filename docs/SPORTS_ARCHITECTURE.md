# Sports Architecture

## Overview
Teamora is a multi-sport platform. Rather than building rules engines that mix sports into the core entities (Teams, Players, Seasons), the system abstracts sport-specific logic and metadata away from the core organizational structures.

## 1. Global Sports Catalogue
The `sports` table serves as a globally managed catalogue of supported sports (e.g., Football, Basketball, Volleyball, Rugby). 
- **ULID-based**: Uses `CHAR(26)` ULIDs for reliable multi-system scalability.
- **Fixed Slugs**: Sports are referenced via slugs (`football`, `basketball`).

## 2. Organization Activation
Teamora operates a "Global Catalogue + Organization Activation" model via the `organization_sports` pivot table.
- Organizations DO NOT automatically have access to all sports.
- An organization must explicitly activate a sport. This prevents UI clutter and allows granular SaaS billing per sport in the future.
- Active state (`is_active`) on the pivot table allows organizations to suspend a sport without deleting historical records.

## 3. Team-Sport Relationship
A `Team` always belongs to EXACTLY ONE `organization_id` and EXACTLY ONE `sport_id`.
- The database enforces this via foreign keys (`fk_team_org`, `fk_team_sport`).
- The uniqueness constraint `uq_team_slug_per_org` allows the SAME team slug (e.g., `senior-men`) to exist for multiple sports, provided the slug includes the sport prefix if handled logically, OR we change the unique key to include `sport_id`. *Currently, `teams` restricts slug uniqueness by org only; in future iterations, we may update the unique key to `(organization_id, sport_id, slug)` to allow identical team slugs across different sports, or prefix team slugs by default.* (For the MVP, we rely on name, but names can be duplicated across sports in the same org).

## 4. Tenant & Sport Routing
To eliminate scope bleeding, tenant and sport are extracted strictly from the URL parameter path:
`/o/{tenant-slug}/s/{sport-slug}/*`

1. **`TenantMiddleware`** validates the `{tenant-slug}` and verifies `organization_user` membership.
2. The middleware also looks for `/s/{sport-slug}`. If present, it validates that the sport exists globally AND is activated by the tenant.
3. Both the `Tenant` entity and `Sport` entity are injected into the Request object for the controller.

## 5. Future Extensibility
Future entities such as `Fixture`, `Result`, and `Standings` will remain fundamentally generic.
- Sport-specific rules (e.g., a "Try" in Rugby vs. a "Goal" in Football) will be modeled using JSON payload schemas or polymorphic rule tables attached to the core generic entities. 
- Standings calculators will dynamically resolve the scoring system based on the `sport_id` associated with a Competition or Season.
