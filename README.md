## Fleet Management Backend

Laravel 13 API for managing stations, trips, seat availability, and bookings, with token-based authentication via Laravel Sanctum.

## Environment requirements

- PHP **8.3+** (project targets modern Laravel; PHP 8.4 also works)
- Composer **2.x**
- Node.js **18+** and npm
- Database:
  - **SQLite** for local setup (default in `.env.example`)
  - **MySQL** configured for tests (`phpunit.xml` currently points to a MySQL `buses` database)

## Install and run the backend application

1. Install PHP dependencies:
   ```bash
   composer install
   ```
2. Create the environment file:
   ```bash
   cp .env.example .env
   ```
   On Windows PowerShell:
   ```powershell
   Copy-Item .env.example .env
   ```
3. Generate the application key:
   ```bash
   php artisan key:generate
   ```
4. Run migrations and seeders:
   ```bash
   php artisan migrate --seed
   ```

## Database setup


### MySQL
1. Create a database (example: `buses`).
2. Update `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=buses
   DB_USERNAME=your_user
   DB_PASSWORD=your_password
   ```
3. Run:
   ```bash
   php artisan migrate --seed
   ```

### Seeded data

`DatabaseSeeder` loads:

- stations
- buses
- seats
- trips
- trip stops
- users
- bookings

## Migrations and seeders

- Run migrations:
  ```bash
  php artisan migrate
  ```
- Run seeders:
  ```bash
  php artisan db:seed
  ```
- Fresh reset + seed:
  ```bash
  php artisan migrate:fresh --seed
  ```

## Run automated tests

This project uses **Pest** on top of Laravel’s testing layer.

- Run all tests:
  ```bash
  php artisan test --compact
  ```
- Or directly with Pest:
  ```bash
  vendor/bin/pest
  ```

> Note: `phpunit.xml` is configured with `DB_CONNECTION=mysql` and `DB_DATABASE=buses` for tests. Ensure that test database exists and credentials are available in your environment.

## API usage and testing

### Base URL

- Local API base (Laravel default): `http://127.0.0.1:8000/api`

### Main endpoints (v1)

Public:

- `POST /api/register`
- `POST /api/login`
- `GET /api/stations`
- `GET /api/trips`
- `GET /api/trips/{trip}`
- `GET /api/trips/{trip}/available-seats`

Authenticated (`auth:sanctum`):

- `POST /api/logout`
- `GET /api/me`
- `GET /api/bookings`
- `POST /api/bookings`
- `GET /api/bookings/{booking}`
- `DELETE /api/bookings/{booking}`

### Authentication flow

1. Register or login.
2. Read token from response (`data.token`).
3. Send it on protected routes:
   ```http
   Authorization: Bearer <token>
   ```

### API documentation

The project includes `darkaonline/l5-swagger` and OpenAPI operation/schema classes under `app/OpenApi/**`, indicating API documentation is annotation/attribute-driven and can be generated via L5 Swagger tooling.

You can view the generated Swagger UI at `/api/documentation` when the app is running.
## Important backend architectural / technical decisions

- **API versioning**: controllers are organized under `App\Http\Controllers\API\V1`.
- **Token auth**: Laravel Sanctum personal access tokens secure protected endpoints.
- **Service layer for seat logic**: `SeatAvailabilityService` centralizes route validation and overlap conflict detection.
- **Transactional booking creation**: booking creation runs in a DB transaction and locks relevant seat bookings to prevent race-condition double booking.
- **Resource responses**: API Resources (`app/Http/Resources/**`) normalize response payloads.
- **OpenAPI-first structure**: dedicated classes in `app/OpenApi/**` keep endpoint contracts explicit.
