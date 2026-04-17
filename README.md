# DSI Technology

A sales management platform built with Laravel 13, Inertia.js v3, React 19, and Material UI 9. Runs on Laravel Sail (Docker).

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.4, Laravel 13, Fortify (auth), Sanctum (API tokens) |
| Frontend | React 19, Inertia.js v3, Material UI 9, Tailwind CSS 4 |
| Database | MySQL 8.4 |
| Cache/Queue | Redis (alpine) |
| Testing | Pest 4, PHPUnit 12 |
| API Docs | L5 Swagger (OpenAPI 3.0) |
| Broadcasting | Laravel Echo 2 + Pusher |
| Exports | DomPDF (PDF), Maatwebsite Excel (XLSX/CSV) |

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop)
- Git

## Installation

```bash
# Clone the repository
git clone <repository-url> dsi
cd dsi

# Copy environment file
cp .env.example .env

# Install PHP dependencies & start Sail
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# Start Docker containers
./vendor/bin/sail up -d

# Generate application key
./vendor/bin/sail artisan key:generate

# Run database migrations
./vendor/bin/sail artisan migrate

# Seed the database (roles, users, sample data)
./vendor/bin/sail artisan db:seed

# Install frontend dependencies & build
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

## Running the Application

```bash
# Start all containers
./vendor/bin/sail up -d

# Start the Vite dev server (for hot-reload during development)
./vendor/bin/sail npm run dev
```

| Service | URL |
|---------|-----|
| Application | http://localhost |
| phpMyAdmin | http://localhost:8080 |
| Vite Dev Server | http://localhost:5173 |

### Default Users

| Email | Password | Role |
|-------|----------|------|
| admin@example.com | password | Admin (full access) |
| manager@example.com | password | Manager |

Customer users are created by the seeder with linked accounts.

## Features

### Roles & Permissions

Three built-in roles with granular permissions managed via Spatie Permission:

- **Admin** - Full access to all modules
- **Manager** - Customers, products, sales, reports, audit log
- **Customer** - Tenant-scoped access to their own products, sales, and reports

### Multi-Tenancy

Customer-role users are scoped to their own data. They can only see products, sales, and reports belonging to their linked customer account. The `TenantScope` class enforces this at the query level.

### Modules

- **Dashboard** - KPIs, revenue/order charts, top products, low stock alerts
- **Customers** - CRUD with search and pagination
- **Products** - CRUD with category filtering, stock tracking, low stock alerts
- **Sales** - Create, edit, delete sales with line items, tax, discount, notes
- **Reports** - Sales reports with date range, filters, PDF/XLSX/CSV export
- **Users** - User management with role assignment
- **Roles & Permissions** - Role-based access control management
- **Audit Log** - Activity tracking powered by Spatie Activity Log

### Authentication

Powered by Laravel Fortify:

- Login / Registration
- Password Reset
- Email Verification
- Two-Factor Authentication (TOTP with QR code and recovery codes)

## API

### Swagger Documentation

The API is documented with OpenAPI 3.0 via L5 Swagger.

**URL:** http://localhost/api/documentation

To regenerate the docs after modifying annotations:

```bash
./vendor/bin/sail artisan l5-swagger:generate
```

During local development, docs auto-regenerate on each request (`L5_SWAGGER_GENERATE_ALWAYS=true`).

### Authentication

The API uses Laravel Sanctum bearer tokens.

```bash
# Get a token
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'

# Use the token
curl http://localhost/api/v1/me \
  -H "Authorization: Bearer <your-token>"
```

### Endpoints

All API routes are prefixed with `/api/v1` and require authentication.

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/v1/auth/login | Issue a Sanctum token |
| POST | /api/v1/auth/logout | Revoke current token |
| GET | /api/v1/me | Authenticated user info |
| GET/POST | /api/v1/customers | List / Create customers |
| GET/PUT/DELETE | /api/v1/customers/{id} | Show / Update / Delete customer |
| GET/POST | /api/v1/products | List / Create products |
| GET/PUT/DELETE | /api/v1/products/{id} | Show / Update / Delete product |
| GET/POST | /api/v1/sales | List / Create sales |
| GET/DELETE | /api/v1/sales/{id} | Show / Delete sale |

## Real-Time Notifications

The app supports real-time broadcasting of sale notifications to admin/manager users via Laravel Echo and Pusher.

### How It Works

1. A sale is created
2. `SaleCreated` event fires via `DB::afterCommit`
3. `SendNewSaleNotification` listener notifies all admin/manager users
4. Notification is stored in the database AND broadcast to each user's private channel
5. The frontend listens via Laravel Echo and shows a toast notification

### Configuration

#### Option 1: Pusher (managed service)

Sign up at [pusher.com](https://pusher.com) (free tier available) and update `.env`:

```env
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=ap1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

Then rebuild the frontend:

```bash
./vendor/bin/sail npm run build
```

#### Option 2: Laravel Reverb (self-hosted)

Install Reverb for a self-hosted WebSocket server (no third-party service needed):

```bash
./vendor/bin/sail artisan install:broadcasting
```

This will install Reverb, update `.env`, and configure the broadcasting driver. You will also need to update `resources/js/echo.ts` to use the `reverb` broadcaster.

#### Testing Broadcasting

To verify the broadcast payload without a WebSocket service:

```bash
# Set broadcast driver to log
# BROADCAST_CONNECTION=log in .env

# Trigger a notification
./vendor/bin/sail artisan tinker --execute '
  $user = App\Models\User::first();
  $sale = App\Models\Sale::latest()->first();
  $user->notify(new App\Notifications\NewSaleNotification($sale));
'

# Check the log
./vendor/bin/sail artisan pail
```

## Development

### Useful Commands

```bash
# Run tests
./vendor/bin/sail artisan test --compact

# Format PHP code
./vendor/bin/sail exec laravel.test vendor/bin/pint --dirty

# Regenerate Wayfinder routes (TypeScript route helpers)
./vendor/bin/sail artisan wayfinder:generate

# View application logs
./vendor/bin/sail artisan pail

# List all routes
./vendor/bin/sail artisan route:list

# Fresh migration with seeding
./vendor/bin/sail artisan migrate:fresh --seed
```

### Composer Scripts

```bash
composer run dev     # Start dev servers (Laravel + Queue + Pail + Vite)
composer run lint    # Fix PHP code style with Pint
composer run test    # Run Pest tests
```

## License

Proprietary.
