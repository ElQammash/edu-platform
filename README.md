# Edu Platform

An online academy for the Egyptian market. Teachers publish recorded and live courses; each lesson can have an assignment and/or a quiz, and students get a computed grade per lesson and per course. The whole platform works in **Arabic (RTL)** and **English**.

The platform is **closed** for now: only the admin creates teacher accounts. The schema is designed so it can later become an open multi-teacher marketplace without a rebuild (teacher profiles already have an approval flag, and roles are not tied to a column on `users`).

This is a monorepo with two apps:

| App | Path | What it does |
| --- | --- | --- |
| **Backend** | [`backend/`](backend) | Laravel REST API (`/api/v1`) for the React app, plus Filament panels for admins (`/admin`) and teachers (`/teacher`) |
| **Frontend** | [`frontend/`](frontend) | React app: public site and everything students use |

## Architecture

```
                         ┌──────────────────────────────────────────────┐
                         │                backend/ (Laravel)            │
 ┌───────────────────┐   │                                              │
 │ frontend/ (React) │   │  /sanctum/csrf-cookie   Sanctum SPA auth     │
 │  localhost:5173   │──▶│  /api/v1/*              REST API             │
 │                   │   │     Controllers/Api/V1 → Form Requests       │
 │ students + guests │   │     → Services → Models → API Resources      │
 └───────────────────┘   │                                              │
   session cookie +      │  /admin     Filament admin panel  (admins)   │
   X-XSRF-TOKEN header   │  /teacher   Filament teacher panel (teachers)│
                         │                                              │
 ┌───────────────────┐   │  spatie/laravel-permission: admin, teacher,  │
 │ admins / teachers │──▶│  student roles · Policies for authorization  │
 │  (browser)        │   └──────────────────────┬───────────────────────┘
 └───────────────────┘                          │
                                                ▼
                                    MySQL (edu_platform)
         Videos are external links (Bunny Stream / YouTube unlisted / Vimeo), never stored on our server.
```

- **Authentication:** the React app uses Sanctum's cookie-based SPA auth. Both apps run on `localhost`, so they share one session. A teacher who logs in on the React app lands in the teacher panel already signed in. `User` also has `HasApiTokens`, so a future mobile app can use personal access tokens.
- **Roles:** stored only in spatie/laravel-permission (there is no `users.role` column). Role-specific data lives in `teacher_profiles` and `student_profiles`.
- **Languages:** the API reads `Accept-Language` (`ar` / `en`) and returns validation and error messages in that language. The React app sends the active language with every request and switches `<html dir>` between `rtl` and `ltr`.

### Repository layout

```
edu-platform/
├── backend/                       Laravel 13
│   ├── app/
│   │   ├── Enums/                 Role enum
│   │   ├── Filament/Admin/        admin panel resources
│   │   ├── Filament/Teacher/      teacher panel resources
│   │   ├── Http/Controllers/Api/V1/
│   │   ├── Http/Middleware/       SetLocale (Accept-Language)
│   │   ├── Http/Requests/Api/V1/  validation
│   │   ├── Http/Resources/V1/     JSON shape
│   │   ├── Models/
│   │   ├── Policies/              (from phase 2)
│   │   └── Services/              business logic (registration, grading, …)
│   ├── database/                  migrations, factories, seeders
│   ├── lang/{ar,en}/              backend translations
│   ├── routes/api.php             /api/v1
│   └── tests/Feature/
├── frontend/                      React + Vite + TypeScript
│   └── src/
│       ├── api/                   axios client (Sanctum-ready)
│       ├── components/ui/         shadcn/ui
│       ├── features/              auth, courses, lessons, assignments, quizzes
│       ├── hooks/  layouts/  pages/  routes/  types/
│       └── locales/               ar.json, en.json
└── .github/workflows/tests.yml    CI for both apps
```

## Tech stack

| | Backend | Frontend |
| --- | --- | --- |
| Language | PHP 8.4 | TypeScript |
| Framework | Laravel 13 | React 19, Vite 8 |
| Auth | Laravel Sanctum (SPA cookies) | axios with `withCredentials` + XSRF |
| Roles | spatie/laravel-permission | — |
| Admin UI | Filament 5 | — |
| Data fetching | — | TanStack Query |
| Routing | `/api/v1` | React Router |
| UI | — | Tailwind CSS 4, shadcn/ui (RTL mode) |
| i18n | Laravel lang files (ar, en) | react-i18next (ar, en) |
| Database | MySQL / MariaDB | — |
| Tests | PHPUnit feature tests, Larastan, Pint | oxlint, `tsc` |

## Features

### Phase 1: setup ✅
- Monorepo with CI for both apps
- Student registration, login, logout and the current user (`/api/v1/auth/*`) over Sanctum SPA auth, rate-limited
- Roles (admin, teacher, student) and teacher/student profiles
- Admin panel: manage users and create teachers with their profiles
- Teacher panel, open only to approved teachers
- React app: routing, Arabic/English with full RTL, language switcher, login/register pages that show the server's validation errors under each field, protected student routes
- Placeholder pages for the catalog, course details, my courses, lesson player, assignment, quiz, my grades and profile

### Roadmap
2. Courses, sections, lessons (recorded), enrollments, lesson progress
3. Assignments, submissions, teacher grading
4. Quizzes, auto-grading + manual essay grading, lesson and course grades, quiz timer
5. Live lessons, attendance, reminder notifications via queues
6. Lesson comments (Q&A), reviews
7. Payments (Paymob), payment-gated enrollment

## Getting started

### Requirements
- PHP 8.3+ and Composer (e.g. via [Laravel Herd](https://herd.laravel.com))
- MySQL 8 or MariaDB 10.4+ (e.g. XAMPP)
- Node.js 20.19+ and npm

### 1. Backend

Create an empty database first, named `edu_platform` (utf8mb4).

```bash
cd backend
composer install
cp .env.example .env          # set DB_* if yours differ (default: root, no password)
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=localhost --port=8000
```

`composer setup` runs the install, env, key and migrate steps in one go.

### 2. Frontend

```bash
cd frontend
npm install
cp .env.example .env          # VITE_API_URL=http://localhost:8000
npm run dev                   # http://localhost:5173
```

> Open both apps on **`localhost`**, not `127.0.0.1`. Sanctum only treats requests from `localhost:5173` as first-party (see `SANCTUM_STATEFUL_DOMAINS` and `FRONTEND_URL` in `backend/.env`), and cookies are only shared across the same host name.

### 3. Run the checks

```bash
cd backend && composer test    # Pint, Larastan and PHPUnit (in-memory SQLite)
cd frontend && npm run lint && npm run build
```

## Demo accounts

`php artisan migrate --seed` creates these accounts outside production. They all use the password **`password`**.

| Role | Email | Where to log in |
| --- | --- | --- |
| Admin | `admin@edu-platform.test` | http://localhost:8000/admin (or the React login, which redirects there) |
| Teacher | `teacher1@edu-platform.test` (Ahmed Samir, Physics) | http://localhost:8000/teacher |
| Teacher | `teacher2@edu-platform.test` (Mona Hassan, Mathematics) | http://localhost:8000/teacher |
| Student | `student1@edu-platform.test` … `student5@edu-platform.test` | http://localhost:5173/login |

Sample courses, lessons, assignments and quizzes are added to the seeders in the phases that create those tables.

## API (v1)

All endpoints are under `/api/v1`. Send `Accept: application/json`, and `Accept-Language: ar` or `en`.

| Method | Endpoint | Auth | Description |
| --- | --- | --- | --- |
| GET | `/sanctum/csrf-cookie` | — | Sets the `XSRF-TOKEN` cookie (call before login/register) |
| POST | `/api/v1/auth/register` | guest | Create a student account and sign in (`name`, `email`, `password`, `password_confirmation`, optional `grade_level`, `parent_phone`) |
| POST | `/api/v1/auth/login` | guest | Sign in (`email`, `password`, optional `remember`) |
| POST | `/api/v1/auth/logout` | user | Sign out |
| GET | `/api/v1/auth/me` | user | The current user with roles and profile |

## Git conventions

[Conventional Commits](https://www.conventionalcommits.org), with a scope for the part of the repo that changed:

- `feat(api)`, `fix(api)`, `test(api)`: Laravel API
- `feat(admin)`: Filament panels
- `feat(web)`, `fix(web)`: React app
- `chore(repo)`, `ci(repo)`, `docs`: repository-wide
