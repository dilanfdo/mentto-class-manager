# Class Manager

Student management system for Sri Lankan tutors and institutes, by NexusDigitalLabs.

- `api/` — Laravel 13 API (Sanctum tokens). One database, every client's rows tagged with `tenant_id`.
- `web/` — React + Vite app: the NexusDigitalLabs admin dashboard (`/admin`) and each client's school app (`/app`).

## Features and the admin dashboard

Every feature a client can have is listed in `api/config/features.php`, matching the public quote page.
"Core" features come with every plan; "addon" features are paid extras. From `/admin/clients/:id`
an admin switches any feature on or off for that client. A switched-off feature:

- disappears from the client's menu and routes in the web app, and
- is refused by the API (`feature:<key>` route middleware returns 403), so it can't be used by calling the API directly.

`students` and `classes` are locked on because everything else depends on them. Suspending a client
locks all of their logins out until they are reactivated.

## Run locally

```bash
cd api && composer install && cp -n .env.example .env && php artisan key:generate && php artisan migrate:fresh --seed
php artisan serve --port=8010
```

```bash
cd web && npm install && npm run dev
```

Open http://localhost:5173. Vite proxies `/api` to the Laravel server on port 8010.

Seeded local logins (password `password`, local only):

| Who | Email |
|---|---|
| NexusDigitalLabs admin | admin@classmanager.test |
| Tutor (Amila Combined Maths) | amila@classmanager.test |
| Institute (Bright Future Institute) | office@brightfuture.test |

## Tests

```bash
cd api && php artisan test
```

Covers feature switching, suspension, admin-only routes, attendance, payments, exam ranking and
that one client can never read or change another client's data.

## Deploying

- API: a Forge server (Singapore), MySQL, `php artisan migrate --force`. Create the first admin with
  `php artisan tinker` (role `platform_admin`, no `tenant_id`).
- Web: Vercel. Set the API host in `web/vercel.json` (`api.example.com` is a placeholder); the rewrite keeps
  the browser on one origin, so clients' own domains work later.
