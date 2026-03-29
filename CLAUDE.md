# Better Doodle

## Overview

A mini-form builder for collecting arbitrary data — event RSVPs, date polling, signups, surveys, etc. Single admin creates forms, public users fill them out anonymously.

Runs at `better-doodle.sf-it.app` behind the `docker-nginx-proxy` reverse proxy.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend + Admin UI | Laravel 12 (PHP 8.4) |
| Database | PostgreSQL 16 |
| Containerization | Docker + Docker Compose |
| Admin UI | Blade + Tailwind CSS v4 (Vite build) + Alpine.js |
| Image Handling | Cropper.js (client-side crop/compress) + GD (server-side) |
| Drag & Drop | SortableJS |
| CI/CD | GitHub Actions -> GHCR -> SSH deploy to Hetzner |

## Architecture

### Data Models

- **Form**: UUID PK. Fields: title, slug (unique), description, header_image, settings (jsonb), active_until, allow_edit, is_active. Auto-generates slug from title. Cascade deletes fields, entries, and uploaded images.
- **FormField**: UUID PK. FK -> form. Fields: type (enum), label, description, options (jsonb), required, sort_order.
- **FormEntry**: UUID PK. FK -> form. Fields: edit_token (unique, nullable), data (jsonb keyed by field UUID).
- **User**: Standard Laravel user, used for admin auth only.

### Field Types

`text`, `textarea`, `select`, `multi_select`, `checkbox`, `date_slots`, `image_upload`

### Routes

- `GET /login`, `POST /login`, `POST /logout` — Admin auth
- `GET /admin/*` — Admin dashboard, form CRUD, entries, settings
- `GET /f/{slug}` — Public form view
- `POST /f/{slug}` — Submit response
- `GET /f/{slug}/thanks` — Thank-you page
- `GET /f/{slug}/edit/{token}` — Edit response (if allow_edit enabled)
- `PUT /f/{slug}/edit/{token}` — Update response

### Timezone Handling

All timestamps stored in UTC. Client submits timezone via hidden input. Admin datetime inputs are converted from user's local timezone to UTC on the server. Public form displays use JavaScript `Intl.DateTimeFormat` to show times in the user's local timezone.

### Image Uploads

- Client-side: Cropper.js for crop/resize, Canvas API for JPEG compression (quality 0.8, max 1920px)
- Sent as base64 in a hidden input
- Server stores in `storage/app/public/uploads/`
- Served via Laravel storage link (`public/storage/`)

### Spam Protection

Honeypot field: hidden input `name="website"` — if filled, submission is silently accepted but not saved.

### Auto-deactivation

Scheduled task runs every minute to deactivate forms past their `active_until` date.

## Docker Setup

### Local Development

`docker compose up -d` uses `docker-compose.yml` + `docker-compose.override.yml` (auto-loaded). Bind-mounts source code, vendor in named volume. Available at `better-doodle.localhost` via jwilder proxy.

### Production

`docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d`. Uses GHCR image. Deployed to `/mnt/data/better-doodle` on Hetzner.

### First Deploy

1. Place `.env` on server at `/mnt/data/better-doodle/.env` (APP_KEY, DB_PASSWORD, APP_ENV=production)
2. Push to main -> GitHub Actions builds, pushes to GHCR, deploys via SSH
3. Entrypoint runs migrations + seeds admin user (`admin@sf-it.at` / `admin1234`)
4. Change default password via Settings page

## Commands

```bash
make build      # Build Docker image
make start      # Start containers
make stop       # Stop containers
make restart    # Restart containers
make logs       # Follow container logs
make migrate    # Run migrations
make seed       # Run seeders
make fresh      # Rebuild from scratch (destroys data)
make bash       # Shell into app container
make db-export  # Export database
make db-import  # Import database
make test       # Run tests
```

## Environment Variables

```env
APP_NAME="Better Doodle"
APP_ENV=production
APP_KEY=        # generate with: php artisan key:generate
APP_URL=https://better-doodle.sf-it.app

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=better_doodle
DB_USERNAME=better_doodle
DB_PASSWORD=    # set a strong password

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```
