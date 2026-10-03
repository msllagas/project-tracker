# Client Project Tracker

Full-stack developer assessment for Koda Kollectiv, by Mandy Llagas.

---

- **Backend:** Laravel 13 REST API (PHP 8.4), PostgreSQL 17, Sanctum cookie authentication
- **Frontend:** Vue 3 + TypeScript single-page app, Vite, Tailwind CSS 4, Vue Router
- **Tests:** Pest (backend, against PostgreSQL), Vitest + Vue Test Utils (frontend)
- **Docker:** one command runs the whole stack; production and development builds

## Quick start (Docker)

All you need is [Docker](https://docs.docker.com/get-docker/).

```bash
docker compose up --build
```

Open **http://localhost:5173** and choose **Use the demo account** on the login page, or log in with:

| Email              | Password   |
| ------------------ | ---------- |
| `demo@example.com` | `password` |

The database is seeded on the first start with the demo account and the 12 projects from [`backend/database/data/test_data.json`](backend/database/data/test_data.json).

```bash
docker compose down        # stop (data is kept)
docker compose down -v     # stop and delete all data; the next start seeds again
```

> Port 5173 must be free. Stop any local Vite dev server first.

## Features

**Required**

- Project list showing client, project, status, priority, start date and due date, 10 projects per page by default (15, 25 or 50 can be chosen)
- Create, edit and delete projects (with a confirmation before deleting)
- Validation: client and project name are required, status and priority must be valid values, and the due date cannot be earlier than the start date. Invalid requests return clear, field-level error messages, which the form shows under each field.

**Bonus**

- **Search** by client or project name (case-insensitive)
- **Filter** by status and by priority
- **Sorting** by newest, due date, priority, status, client name or project name. Priority and status sort in their logical order (High → Low, Planning → Completed), not alphabetically, and projects without a date always come last.
- **Authentication**: log in and log out, using Laravel Sanctum's cookie-based SPA authentication. There is no public sign-up; see [Design decisions](#design-decisions).
- **Unit and feature tests**: 73 backend tests and 55 frontend tests
- **Docker setup**: multi-stage images with `dev` and `prod` targets

**Extras**

- Filters and the current page are stored in the URL (`/?status=on_hold&sort=-priority&page=2`), so a view survives a refresh and can be shared
- Overdue projects (past their due date and not completed) are highlighted with the number of days overdue
- Responsive layout: a table on wide screens, cards on phones
- Accessible forms and dialogs: labelled controls, errors linked to their fields, visible keyboard focus

## Development

### With Docker (hot reload)

```bash
docker compose -f compose.yaml -f compose.dev.yaml up --build --renew-anon-volumes
```

- The backend and frontend source folders are mounted into the containers, so changes reload automatically. The app is again at http://localhost:5173.
- `--renew-anon-volumes` makes sure the containers pick up newly added npm packages.
- Artisan commands run inside the backend container, for example `docker compose -f compose.yaml -f compose.dev.yaml exec backend php artisan route:list`.

### Without Docker

Requirements: PHP 8.3+ with `pdo_pgsql`, Composer, Node.js 22+, and PostgreSQL.

1. Create two databases: `backend` for the app and `backend_test` for the test suite.
2. Set up and start the backend:

   ```bash
   cd backend
   composer install
   cp .env.example .env    # then set DB_USERNAME and DB_PASSWORD
   php artisan key:generate
   php artisan migrate --seed
   php artisan serve       # http://localhost:8000
   ```

3. In a second terminal, start the frontend:

   ```bash
   cd frontend
   npm ci
   npm run dev             # http://localhost:5173
   ```

Open http://localhost:5173. Vite forwards `/api` and `/sanctum` requests to Laravel on port 8000, so the browser sees one address and the session cookies work without any CORS setup.

## Running the tests

| Suite    | With Docker                             | Without Docker                             |
| -------- | --------------------------------------- | ------------------------------------------ |
| Backend  | `docker compose run --rm backend-test`  | `cd backend && php artisan test`           |
| Frontend | `docker compose run --rm frontend-test` | `cd frontend && npm test`                  |

The backend tests run against the PostgreSQL `backend_test` database (the same database engine as the app, so database-specific behaviour is tested too). They never touch the app's data.

Other frontend checks: `npm run lint`, `npm run format`, and `npm run build` (which includes type-checking).

## API reference

All endpoints are under `/api`, so `GET /projects` from the brief is `GET /api/projects`. Requests and responses use JSON; send `Accept: application/json`.

### Authentication

The API uses Sanctum's [SPA authentication](https://laravel.com/docs/sanctum#spa-authentication): a session cookie plus CSRF protection, rather than API tokens. A client must:

1. Call `GET /sanctum/csrf-cookie` to receive an `XSRF-TOKEN` cookie.
2. Send that cookie's value in an `X-XSRF-TOKEN` header on every `POST`, `PUT` and `DELETE` request.
3. Send an `Origin` (or `Referer`) header from a trusted address, such as `http://localhost:5173`.

Trying it with curl:

```bash
curl -c cookies.txt http://localhost:5173/sanctum/csrf-cookie
TOKEN=$(grep XSRF-TOKEN cookies.txt | awk '{print $7}' | sed 's/%3D/=/g; s/%2B/+/g; s/%2F/\//g')

curl -b cookies.txt -c cookies.txt http://localhost:5173/api/login \
  -H "Origin: http://localhost:5173" -H "Accept: application/json" \
  -H "X-XSRF-TOKEN: $TOKEN" -H "Content-Type: application/json" \
  -d '{"email":"demo@example.com","password":"password"}'

curl -b cookies.txt http://localhost:5173/api/projects \
  -H "Origin: http://localhost:5173" -H "Accept: application/json"
```

| Method | Endpoint        | Description                                                  |
| ------ | --------------- | ------------------------------------------------------------ |
| POST   | `/api/login`    | Log in (`email`, `password`, optional `remember`); 5 attempts per minute |
| POST   | `/api/logout`   | Log out                                                      |
| GET    | `/api/user`     | The logged-in user                                           |

### Projects

All project endpoints require a logged-in user. The whole API allows 60 requests per minute per user (or per IP address when logged out); login also has its own, stricter limit.

| Method | Endpoint             | Description                               | Success |
| ------ | -------------------- | ----------------------------------------- | ------- |
| GET    | `/api/projects`      | List a page of projects (see query parameters) | 200 |
| GET    | `/api/projects/{id}` | Get one project                           | 200     |
| POST   | `/api/projects`      | Create a project                          | 201     |
| PUT    | `/api/projects/{id}` | Update a project (send every field)       | 200     |
| DELETE | `/api/projects/{id}` | Delete a project                          | 204     |

**Query parameters for `GET /api/projects`**

| Parameter  | Example                  | Description                                                     |
| ---------- | ------------------------ | --------------------------------------------------------------- |
| `search`   | `?search=acme`           | Client or project name contains the text (case-insensitive)     |
| `status`   | `?status=on_hold`        | `planning`, `in_progress`, `on_hold` or `completed`             |
| `priority` | `?priority=high`         | `low`, `medium` or `high`                                       |
| `sort`     | `?sort=-due_date`        | `client_name`, `name`, `status`, `priority`, `start_date`, `due_date` or `created_at`; prefix with `-` for descending. Default: `-created_at` |
| `page`     | `?page=2`                | Page number, from 1. Default: `1`                               |
| `per_page` | `?per_page=25`           | Projects per page, from 1 to 100. Default: `10`                 |

The list response wraps the projects in Laravel's pagination format. `links` holds the first, last, previous and next page URLs (keeping the other query parameters), and `meta` holds the counts:

```jsonc
{
  "data": [ /* projects, as below */ ],
  "links": { "first": "…?page=1", "last": "…?page=2", "prev": null, "next": "…?page=2" },
  "meta": { "current_page": 1, "last_page": 2, "per_page": 10, "from": 1, "to": 10, "total": 12, "path": "…", "links": [ /* … */ ] }
}
```

**A project**

```json
{
  "data": {
    "id": 1,
    "client_name": "Acme Corporation",
    "name": "Corporate Website Redesign",
    "description": "Redesign and modernize the company's corporate website.",
    "status": "in_progress",
    "priority": "high",
    "start_date": "2026-06-01",
    "due_date": "2026-07-15",
    "created_at": "2026-10-03T07:42:27.000000Z",
    "updated_at": "2026-10-03T07:42:27.000000Z"
  }
}
```

**Validation rules (create and update)**

| Field         | Rules                                                                |
| ------------- | -------------------------------------------------------------------- |
| `client_name` | required, text, max 255 characters                                    |
| `name`        | required, text, max 255 characters (the project name)                 |
| `description` | optional, text, max 5000 characters                                   |
| `status`      | required, one of `planning`, `in_progress`, `on_hold`, `completed`    |
| `priority`    | required, one of `low`, `medium`, `high`                              |
| `start_date`  | optional, a real date in `YYYY-MM-DD` format                          |
| `due_date`    | optional, a real date in `YYYY-MM-DD` format, not earlier than `start_date` |

### Errors

Errors always come back as JSON with a `message`; validation errors also list the problems per field.

```jsonc
// 422 Unprocessable Content
{
  "message": "The client name field is required. (and 3 more errors)",
  "errors": {
    "client_name": ["The client name field is required."],
    "name": ["The project name field is required."],
    "status": ["The status must be one of: planning, in_progress, on_hold, completed."],
    "due_date": ["The due date cannot be earlier than the start date."]
  }
}
```

| Status | When                                                                    | Example `message`                                                      |
| ------ | ----------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| 401    | Not logged in                                                           | `Unauthenticated.`                                                     |
| 403    | Login or logout without a trusted `Origin`                              | `This endpoint only accepts requests from the frontend application.`   |
| 404    | The project does not exist (including IDs that are not positive integers) | `Project not found.`                                                   |
| 419    | Missing or expired CSRF token                                           | `CSRF token mismatch.`                                                 |
| 422    | Invalid input (see above)                                               | `The due date cannot be earlier than the start date.`                  |
| 429    | More than 60 requests a minute, or more than 5 login attempts; `Retry-After` gives the wait in seconds | `Too Many Attempts.`                                                   |

## Project structure

```
├── compose.yaml            Production-like stack, plus backend-test and frontend-test
├── compose.dev.yaml        Development overrides (hot reload, mounted source)
├── backend/                Laravel API
│   ├── app/Enums/          ProjectStatus, ProjectPriority
│   ├── app/Http/           Controllers, form requests (validation), resources, middleware
│   ├── app/Models/         Project (search and sort scopes), User
│   ├── database/           Migrations, factories, seeders, data/test_data.json
│   ├── docker/             Container entrypoint and PHP-FPM config
│   └── tests/Feature/      Pest feature tests
└── frontend/               Vue SPA
    ├── docker/             nginx config for the production image
    └── src/
        ├── api/            fetch wrapper (CSRF, errors) and typed endpoint functions
        ├── components/     Shared UI and project components (list, toolbar, form dialog)
        ├── composables/    Auth state, URL filters, list loading, toasts
        ├── router/         Routes and login guards
        ├── utils/          Date, overdue and filter helpers
        └── views/          Login, Projects
```

## Design decisions

- **Backend and frontend live in one repository.** Keeping the Laravel API and the Vue app together means one `docker compose up` runs and tests the whole app, and a change that touches both sides lands in one commit. For a larger team, with separate owners for the frontend and the backend, I'd split them into two repositories so each can be versioned, reviewed and deployed on its own.
- **Cookie-based authentication instead of API tokens.** Sanctum's SPA mode keeps the session in an `HttpOnly` cookie that page scripts cannot read, so a cross-site scripting bug cannot steal it, and CSRF protection guards state-changing requests. The frontend and API are served from one address (the Vite proxy in development, nginx in production), so no CORS configuration is needed.
- **No public sign-up.** This is an internal tool for an agency's staff, so accounts are created by the agency, not by anyone who finds the login page.
- **Validation lives in form requests and uses PHP enums.** Each endpoint that takes input has its own form request (`ListProjectsRequest`, `StoreProjectRequest`, `UpdateProjectRequest`), so create and update can change independently even though their rules are the same today. Status and priority are backed enums, which are the single source of valid values for the database casts, validation rules, error messages and sorting.
- **`PUT` replaces the whole project.** The update request has the same rules as create, so every field is validated together (for example, the due date against the start date). `PATCH` is also routed by Laravel but expects the same full payload.
- **Sorting and filtering use allow-lists.** Sort columns and filter values are validated before they reach the query, so user input never becomes part of the SQL.
- **The seed data comes from `test_data.json`.** The seeder maps its camelCase keys and display labels (`"In Progress"`) to the API's column names and enum values (`in_progress`). It leaves IDs to the database so its ID sequence stays correct.
- **The project list is cached.** Each page of the list (one cache entry per query string) is kept with no expiry in Laravel's default database cache store, as plain JSON data, since Laravel refuses to unserialize objects from the cache by default. Every cache key contains a version value; creating, updating or deleting a project through the API forgets that value, so all cached pages go stale at once. The database store has no cache tags, which is why it uses a version value rather than tags. Changes made outside the API (seeders, Tinker, direct database edits) do not clear the cache; run `php artisan cache:clear` after making them.
- **Errors are handled in one place on each side.** The API always answers `/api` requests with JSON, never an HTML error page, and turns a missing project into `Project not found.`. The frontend's `fetch` wrapper turns every failure into an `ApiError`: validation errors appear under their form fields, an expired session returns to the login page, and rate limits and server errors get a plain-language message instead of the server's own wording (which, with debugging on, can include SQL).
- **Tests run on PostgreSQL, not SQLite**, so database-specific behaviour (case-insensitive search, null ordering, raw SQL) is tested on the engine the app uses.

## Possible improvements

- A user management screen where an admin adds team members, with roles
- Per-user or per-team project ownership, with authorization policies
- Showing the full description in the list, for example in an expandable row
- End-to-end browser tests (for example with Playwright) in a CI pipeline
