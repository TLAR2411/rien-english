# 📚 Learning English

Starter project (cut down from Loan System V1) with a Laravel API and a Vue 3 (Vuetify) frontend.
It keeps only the parts every project needs:

- **Login** (Passport) and the user profile
- **Admin part**: users, user requests, team structure, roles & permissions, positions,
  branches, departments, settings, activity log, address (province → village)
- **HR part**: employees (list, create, edit, profile, terminate, former employees)
- **All shared components** in `WEB/src/components` and `WEB/src/@core`

After `migrate --seed` the admin user is `admin`. Its password comes from `ADMIN_PASSWORD`; if that is empty, a random one is printed once by the seeder.

### Start a new project from this template

1. Copy this folder and rename it.
2. API: set `APP_NAME`, `APP_URL`, `OAUTH_URL`, `DB_DATABASE` in `API/.env`; WEB: set `VITE_API_URL` in `WEB/.env`.
3. Create a new Passport client (see below) – don't reuse the template's secret.
4. Edit the seeders in `API/database/seeders` (company, branch, roles, positions, settings).
5. Add a new system part (e.g. `stock`): a nav file in `WEB/src/navigation/vertical/`,
   an entry in `WEB/src/composables/useSystemParts.js`, a redirect in
   `WEB/src/router/additional-routes.js`, pages under `WEB/src/pages/<part>/`,
   a `<part>-allow-part` permission in `PermissionSeeder`, and the value in the
   `users.default_part` enum.

- `API/` — Laravel 12 backend
- `WEB/` — Vue 3 + Vite frontend

## Requirements

- PHP >= 8.2, Composer
- MySQL
- Node.js (LTS) + npm
- Redis (optional, used for cache/queue if configured)

## API Setup

```bash
cd API
composer install
cp .env.example .env
php artisan key:generate
```

Update `.env` with your database credentials (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), then run:

```bash
php artisan migrate --seed
php artisan storage:link
```

### Passport (OAuth) setup

Login uses Laravel Passport (password grant), so you need encryption keys and a password client:

```bash
php artisan passport:keys
php artisan passport:client --password
```

The client command prompts for a client name and prints a **Client ID** and **Client Secret**:
- Put the secret in `.env` as `PASSPORT_CLIENT_SECRET`
- Keep the client ID handy — it's needed as the `X-CLIENT-ID` header (see Postman setup below and `VITE_CLIENT_ID` in `WEB/.env`)

### Run the API

```bash
php artisan serve
```

Or run server, queue, logs, and Vite together:

```bash
composer run dev
```

## WEB Setup

```bash
cd WEB
npm install
cp .env.example .env
```

Update `VITE_API_URL` in `.env` to point to your running API.

### Run the WEB app

```bash
npm run dev
```

App runs at `http://localhost:5173` by default.

## Build for production

```bash
cd WEB
npm run build
```


## Postman Setup

All endpoints live under `/api/web`, e.g. `http://learning-english.test/api/web/login`.

1. Create a Postman environment with:
   - `base_url` = `http://learning-english.test` (your `APP_URL`)
   - `client_id` = the Passport client ID from `passport:client --password` above
   - `access_token` = (leave empty, filled in after login)

2. **Login** — `POST {{base_url}}/api/web/login`
   - Headers: `X-CLIENT-ID: {{client_id}}`
   - Body (JSON): `{ "username": "...", "password": "..." }`
   - Response returns `data.access_token.value` — save it to the `access_token` environment variable

3. **Authenticated requests** — for any route behind `auth:api` (e.g. `bootstrap`, `logout`, most other endpoints):
   - Header: `Authorization: Bearer {{access_token}}`

4. **Refresh token** — `POST {{base_url}}/api/web/refresh` with header `X-CLIENT-ID` when the access token expires.
