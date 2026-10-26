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
│   │   ├── Enums/                 roles, course/lesson types, statuses, settings keys
│   │   ├── Filament/Admin/        admin panel resources
│   │   ├── Filament/Teacher/      teacher panel resources
│   │   ├── Filament/Shared/       course/section/lesson UI used by both panels
│   │   ├── Http/Controllers/Api/V1/
│   │   ├── Http/Middleware/       SetLocale (Accept-Language)
│   │   ├── Http/Requests/Api/V1/  validation
│   │   ├── Http/Resources/V1/     JSON shape
│   │   ├── Models/
│   │   ├── Policies/              course, section and lesson access
│   │   ├── Services/              business logic (enrollment, progress, settings, …)
│   │   └── Support/VideoUrl.php   Bunny / YouTube / Vimeo link parsing
│   ├── database/                  migrations, factories, seeders
│   ├── lang/{ar,en}/              backend translations
│   ├── routes/api.php             /api/v1
│   └── tests/Feature/
├── frontend/                      React + Vite + TypeScript
│   └── src/
│       ├── api/                   axios client (Sanctum-ready)
│       ├── components/ui/         shadcn/ui
│       ├── features/              auth, courses, lessons, profile, teachers, …
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
| Content | Markdown (descriptions, lesson notes) | react-markdown (no raw HTML) |
| Video | External links only: Bunny Stream, YouTube, Vimeo | YouTube IFrame API, `@vimeo/player`, `player.js` (Bunny) |
| Tests | PHPUnit feature tests, Larastan, Pint | oxlint, `tsc` |

## Features

### Phase 1: setup ✅
- Monorepo with CI for both apps
- Student registration, login, logout and the current user (`/api/v1/auth/*`) over Sanctum SPA auth, rate-limited
- Roles (admin, teacher, student) and teacher/student profiles
- Admin panel: manage users and create teachers with their profiles
- Teacher panel, open only to approved teachers
- React app: routing, Arabic/English with full RTL, language switcher, login/register pages that show the server's validation errors under each field, protected student routes
- Placeholder pages for the pages of later phases

### Phase 2: courses, lessons, enrollments and progress ✅
- **Courses** with a teaching language (ar/en), type (recorded, live, hybrid), price in EGP and draft/published status; **sections** and **lessons** (video or text, optional free preview) ordered by drag and drop
- **Videos are links, never uploads.** Bunny Stream, YouTube and Vimeo links are validated and converted to embeds
- **Enrollment:**
  - free courses: students enroll themselves in one click;
  - paid courses: the "Contact us" button opens WhatsApp with a message naming the course (and the student's email), and the admin enrolls the student manually (`payment_status = manual`) until Paymob arrives in phase 7
- **Progress:** the player reports how far the student watched. A video lesson completes at 90%, and every lesson has a "mark as complete" button. Course progress is shown as a percentage
- **Access rules (policies):**
  - the course curriculum is public, but lesson content and video links are only sent to enrolled students, the course teacher and admins;
  - free previews are open to everyone;
  - teachers can only reach their own courses
- **Teacher panel** (`/teacher`): own courses → sections → lessons
- **Admin panel** (`/admin`): all courses with teacher assignment, manual enrollment, and an **Academy settings** page for the WhatsApp number (falls back to `ACADEMY_WHATSAPP_NUMBER` in `.env`)
- **React pages:**
  - course catalog (search plus language, type and price filters, all kept in the URL; pagination);
  - course details (curriculum, enroll / WhatsApp / continue);
  - my courses with progress bars;
  - lesson player with the course outline, previous/next lesson and progress;
  - student profile with avatar upload;
  - public teacher profiles
- Teacher-written text is Markdown, rendered without raw HTML (no script injection); mixed Arabic/English text uses `dir="auto"`

### Phase 3: assignments, submissions and grading ✅
- **Assignments:** each lesson can have one, added from the lesson form in the panels, with Markdown instructions, an optional due date and a max score
- **Submissions:**
  - text and/or one file (PDF, JPG, PNG or WebP, up to 10 MB, checked by real content type);
  - one per student per assignment, editable until it is graded;
  - accepted after the deadline but flagged **late**. The flag is computed, so extending a deadline clears it
- **Private files:**
  - stored in `storage/app/private`, never under `public/`;
  - shown only to the student, the course teacher and admins: panels use the session, the React app gets a 30-minute signed link
- **Grading:**
  - the teacher panel's **Grading** page is a queue of pending submissions across all the teacher's courses (oldest first, late badge, count in the sidebar);
  - the grading window shows the answer and file, and takes a score (up to the max) and feedback;
  - re-grading is allowed;
  - admins can grade everything
- **Notifications:**
  - students get an in-app notification when their work is graded or re-graded: a bell with an unread count in the React app;
  - notifications store data, not text, so they read in each user's language
- **Arabic panels:**
  - both Filament panels switch between Arabic (RTL) and English from the user menu or the login page;
  - the choice is saved on the account (`users.locale`);
  - the strings Filament's own Arabic was missing are added in `lang/vendor`
- **Teacher profile:** at `/teacher/profile`, teachers edit their name, password, specialty, bio and photo

### Phase 4: quizzes and grades ✅
- **Quizzes:**
  - one per lesson, added from the lesson form;
  - questions are written on the **Quizzes** page: multiple choice (exactly one correct option), true/false, or essay, each with points, in drag-and-drop order;
  - settings: time limit (optional), pass mark (%), attempts allowed (default 1), due date (optional), when to show answers, and shuffling;
  - questions lock once anyone has taken the quiz
- **Taking a quiz:**
  - the server sets the deadline (start + time limit, never past the due date) and refuses late answers; an attempt left open is closed at its deadline;
  - the countdown follows the server's clock, answers autosave, and the quiz submits itself at 0;
  - with shuffling on, each student gets their own question and option order, kept across refreshes
- **Grading:**
  - MCQ and true/false are graded on submit;
  - essays wait in **Grading → Quizzes**, and the student is notified when the teacher finishes them;
  - with several attempts, the best one counts
- **Showing answers:**
  - per quiz: right after grading, after the due date, or never (score only);
  - the default is after the due date if there is one, otherwise right after grading;
  - `is_correct`, the correct option and per-question points are never sent before the attempt is submitted and fully graded
- **Grades** (computed, never stored):
  - lesson grade = the equal-weighted average of its assignment and quiz percentages (one alone counts fully);
  - course grade = the average of lesson grades;
  - missing work counts as **0** once its due date has passed; work not due yet (or with no due date) is left out; "X of Y done" keeps the gaps visible;
  - the **My grades** page shows it all

### Phase 5: live lessons, attendance and reminders ✅
- **Live lessons:**
  - a lesson type with a start time (Cairo time), duration, a Zoom / Google Meet / Microsoft Teams link (https only; hosts in `config/academy.php`) and an optional recording link;
  - after the session, the recording plays in the regular player
- **The meeting link is protected:**
  - it is never in the lesson data or the page;
  - students get it from **Join**, which only works from 15 minutes before the start until the end;
  - the lesson page shows the state (upcoming / live now / ended) and countdowns by the server's clock;
  - an `.ics` file adds the session to Google Calendar, Outlook or an iPhone
- **Attendance:**
  - recorded automatically when a student clicks Join during the session;
  - teachers correct it on the lesson's **Attendance** sheet in the panel;
  - attending completes the lesson for progress;
  - it does **not** affect grades: **My grades** shows the attendance percentage next to each course's grade
- **Reminders:**
  - in-app and email, 24 hours and 1 hour before each session, in the student's language (saved on the account when they switch language in the app);
  - each reminder goes out exactly once per scheduled time;
  - moving a session notifies students and re-arms its reminders; cancelling it (changing the type or deleting it) notifies them too;
  - all notifications go through the queue
- **Teacher dashboard:** upcoming sessions, with Start meeting and the attendance sheet
- **My courses:** lists the student's upcoming live sessions

### Phase 6: lesson Q&A and course reviews ✅
- **Lesson Q&A:**
  - shared by everyone in the course, at the bottom of the lesson page;
  - enrolled students ask; classmates, the teacher and admins reply (one level of replies), and staff replies carry a **Teacher** badge;
  - authors edit a post for 15 minutes and delete it any time; the teacher and admins delete any post (a deleted question keeps its replies);
  - the student who asked, or the teacher, marks a question **resolved** or **accepts a reply** as the answer (which resolves it);
  - filters: all, unresolved, my questions; posting is limited to 10 per minute and 2,000 characters
- **Teacher Q&A inbox** (panel → Q&A, with a badge):
  - opens on **unanswered** questions, oldest first; a question counts as answered once the teacher or an admin replies (a classmate's reply doesn't count);
  - one modal shows the thread, with reply, accept answer and resolve/reopen;
  - the dashboard lists questions still waiting; admins see every question
- **Q&A notifications:**
  - in-app for every new question (to the teacher), reply (to the asker and the others in the thread) and accepted answer; students see them in the app's bell, staff in the panel's bell (polled every 60 seconds);
  - **email** goes to a student only when the teacher replies to their question or accepts an answer on it;
  - teachers get no email per question, but one **daily digest** at 08:00 listing their unanswered questions (none sent when there are none, at most one a day)
- **Course reviews:**
  - enrolled students who have done **30%** of a course rate it 1–5 stars, with an optional comment (1,000 characters); one review per course, editable and deletable;
  - the course page shows the average, a 5-to-1 star breakdown and the reviews (first names only), with the teacher's public reply;
  - catalog cards show the rating, and the catalog sorts by **highest rated**; teacher profiles show the average of all their courses' reviews
- **Moderation:** teachers reply to reviews of their courses in the panel (one reply each); admins **hide** reviews that break the rules (they disappear and stop counting in the rating) and can show them again

### Roadmap
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
php artisan storage:link      # serves uploaded avatars and course thumbnails
php artisan serve --host=localhost --port=8000
```

`composer setup` runs the install, env, key and migrate steps in one go.

Updating an existing install after a pull: `composer install && php artisan migrate`. Phase 3 includes a migration that stops MySQL/MariaDB from resetting `enrollments.enrolled_at`. Run `php artisan migrate:fresh --seed` to get the newest demo data.

Set the academy's WhatsApp number (international format, e.g. `201012345678`) in **Admin → Academy settings**, or as `ACADEMY_WHATSAPP_NUMBER` in `backend/.env`. Until it is set, paid courses say contact details are coming soon.

### 2. Frontend

```bash
cd frontend
npm install
cp .env.example .env          # VITE_API_URL=http://localhost:8000
npm run dev                   # http://localhost:5173
```

> Open both apps on **`localhost`**, not `127.0.0.1`. Sanctum only treats requests from `localhost:5173` as first-party (see `SANCTUM_STATEFUL_DOMAINS` and `FRONTEND_URL` in `backend/.env`), and cookies are only shared across the same host name.

### 3. Queue and scheduler (reminders, notifications, emails)

Notifications and emails are queued, and live lesson reminders and the teachers' daily Q&A digest are scheduled. Locally, run the scheduler in a second terminal. It runs every task each minute, including a queue worker that empties the queue and exits:

```bash
cd backend && php artisan schedule:work
```

To send everything due right now, once: `php artisan schedule:run`. To send the Q&A digest without waiting for 08:00: `php artisan qa:send-teacher-digest`, then `php artisan schedule:run`. Emails are written to `storage/logs/laravel.log` (`MAIL_MAILER=log`).

### 4. Run the checks

```bash
cd backend && composer test    # Pint, Larastan and PHPUnit (in-memory SQLite)
cd frontend && npm run lint && npm run build
```

## Deploying the backend on shared hosting (Hostinger)

Shared hosting allows **one cron job and no long-running processes** (no `queue:work` daemon, no supervisor). The app is built for that:

- `routes/console.php` runs, every minute and in this order:
  1. `lessons:send-live-reminders`: queues due reminders;
  2. `qa:send-teacher-digest` (daily at 08:00 only): queues each teacher's digest of unanswered questions;
  3. `queue:work --stop-when-empty --max-time=50 --tries=3`: sends everything queued, then exits.

  All three use `withoutOverlapping()`, so a slow run never starts a second worker. The overlap lock uses the database cache (`CACHE_STORE=database`), so no Redis is needed.
- Failed jobs are kept for a week (`queue:prune-failed`, daily). Run `php artisan queue:failed` to see them and `php artisan queue:retry all` to retry them.

Setup:

1. Point the domain (or subdomain) at `backend/public`, run `composer install --no-dev --optimize-autoloader`, copy `.env.example` to `.env` and set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `FRONTEND_URL`, `SANCTUM_STATEFUL_DOMAINS` and the database settings. Then run:

   ```bash
   php artisan key:generate
   php artisan migrate --force
   php artisan storage:link
   php artisan optimize
   ```

2. Mail: create a mailbox in hPanel and use it in `.env`:

   ```dotenv
   MAIL_MAILER=smtp
   MAIL_SCHEME=smtps
   MAIL_HOST=smtp.hostinger.com
   MAIL_PORT=465
   MAIL_USERNAME=no-reply@your-domain.com
   MAIL_PASSWORD=your-mailbox-password
   MAIL_FROM_ADDRESS=no-reply@your-domain.com
   MAIL_FROM_NAME="Edu Platform"
   ```

3. Add the **single cron job** in hPanel → Advanced → Cron Jobs, to run every minute. Use the full PHP path from hPanel if `php` is not on the cron PATH:

   ```
   * * * * * cd /home/USER/domains/your-domain.com/backend && php artisan schedule:run >> /dev/null 2>&1
   ```

4. Check it: `php artisan schedule:list` shows the tasks, and after a minute the `jobs` table should be empty.

> **Optional, for a future VPS:** with long-running processes allowed, you can run a permanent worker under supervisor (`php artisan queue:work --sleep=3 --tries=3`) and remove the `queue:work` entry from `routes/console.php`. Keep the cron entry for the reminders.

## Demo accounts

`php artisan migrate --seed` creates these accounts outside production. They all use the password **`password`**.

| Role | Email | Where to log in |
| --- | --- | --- |
| Admin | `admin@edu-platform.test` | http://localhost:8000/admin (or the React login, which redirects there) |
| Teacher | `teacher1@edu-platform.test` (Ahmed Samir, Physics) | http://localhost:8000/teacher |
| Teacher | `teacher2@edu-platform.test` (Mona Hassan, Mathematics) | http://localhost:8000/teacher |
| Student | `student1@edu-platform.test` … `student5@edu-platform.test` | http://localhost:5173/login |

### Demo courses

| Course | Teacher | Language | Price | Enrolled demo students |
| --- | --- | --- | --- | --- |
| الفيزياء للصف الثالث الثانوي (`physics-secondary-3`) | Ahmed | Arabic | Free | student1 (40% done), student3, student4 and student5 (40% each) |
| Physics Olympiad Preparation | Ahmed | English | 750 EGP | student3 (manual) |
| الرياضيات البحتة - التفاضل والتكامل | Mona | Arabic | 500 EGP | student2 (manual) |
| Algebra Foundations | Mona | English | Free | student1 |
| الهندسة الفراغية (draft) | Mona | Arabic | 300 EGP | not visible to students |

Each course has two sections of video and text lessons, and its first lesson is a free preview. The videos are Blender open movies standing in for real lessons. Use student4 or student5 to try enrolling in another course from scratch.

### Demo assignments

| Assignment | Lesson (course) | Deadline | Submissions |
| --- | --- | --- | --- |
| مسائل على قانون أوم (/20) | قانون أوم (Physics) | passed | student1: **graded 18/20** with a PDF (so student1 has a notification); student3: **late**, waiting |
| تقرير عن قاعدة اليد اليمنى (/10) | المجال المغناطيسي لسلك مستقيم (Physics) | in 5 days | student1: waiting |
| تمارين على قواعد الاشتقاق (/50) | قواعد الاشتقاق (Calculus) | in a week | student2: waiting, with a PDF |
| Solve five linear equations (/25) | Linear equations (Algebra) | none | none: try submitting as student1 |

Sign in to `/teacher` as teacher1 to see the grading queue.

### Demo quizzes

| Quiz | Lesson (course) | Setup | State |
| --- | --- | --- | --- |
| اختبار سريع على التيار الكهربي | التيار الكهربي وشدته (Physics) | 10 min, 3 questions | student1: auto-graded **3/5 (60%, passed)**, answers shown |
| اختبار قانون أوم | قانون أوم (Physics) | 20 min, MCQ + essay, due in 3 days, answers after the due date | student3: **essay waiting** for teacher1 |
| اختبار النهايات | النهايات والاتصال (Calculus) | due 2 days ago | student2 never took it: **counts as 0** in My grades |
| Expressions check | Variables and expressions (Algebra) | untimed, shuffled, 2 attempts | nobody yet: try it as student1 |

### Demo live lessons

Times are relative to when you seed, so every state shows right away. Meeting links are placeholders.

| Session | Course | When (from seeding) | State |
| --- | --- | --- | --- |
| حصة مراجعة الكهربية (لايف) | Physics (free) | 2 days ago | ended, with a recording; student1 attended, student3 missed it |
| حصة حل مسائل (شغالة دلوقتي) | Physics | started 10 minutes ago | **live now**: Join works |
| حصة أسئلة وأجوبة | Physics | in 55 minutes | Join opens 15 minutes before; the 1-hour reminder goes out on the first scheduler run |
| حصة المراجعة النهائية | Physics | in 23 hours | the 24-hour reminder goes out on the first scheduler run |
| Problem night #1 / #2 | Physics Olympiad | a week ago / in 6 days | recording + attendance (student3) / upcoming |

After seeding, run `php artisan schedule:run` once and log in as student1. The bell shows the two reminders, and the emails are in `storage/logs/laravel.log`.

### Demo Q&A and reviews

| Where | What | Try it |
| --- | --- | --- |
| التيار الكهربي وشدته (Physics, lesson 1) | student1's question, answered by teacher1 and **accepted** (resolved); student3's question **waiting** | as student1, ask a question; as teacher1, answer it from **Q&A** in the panel |
| قانون أوم (Physics) | student4's question, answered only by **a classmate** (student1), so still waiting for the teacher | |
| Physics Olympiad, first lesson | student3's question, waiting | |
| Physics reviews | 5★ from student3 (with teacher1's reply), 4★ from student4, and a 1★ spam review from student5 **hidden** by the admin: average **4.5** from 2 reviews | as student1 (40% done), rate the course on its page |
| Olympiad reviews | 4★ from student3 | |

teacher1 has 3 unanswered questions: run `php artisan qa:send-teacher-digest` and then `php artisan schedule:run`, and the digest email is in `storage/logs/laravel.log`.

## API (v1)

All endpoints are under `/api/v1`. Send `Accept: application/json`, and `Accept-Language: ar` or `en`.

| Method | Endpoint | Auth | Description |
| --- | --- | --- | --- |
| GET | `/sanctum/csrf-cookie` | — | Sets the `XSRF-TOKEN` cookie (call before login/register) |
| POST | `/api/v1/auth/register` | guest | Create a student account and sign in (`name`, `email`, `password`, `password_confirmation`, optional `grade_level`, `parent_phone`) |
| POST | `/api/v1/auth/login` | guest | Sign in (`email`, `password`, optional `remember`) |
| POST | `/api/v1/auth/logout` | user | Sign out |
| GET | `/api/v1/auth/me` | user | The current user with roles and profile |
| GET | `/api/v1/settings` | — | Public academy settings (`whatsapp_number`) |
| GET | `/api/v1/courses` | — | Published courses, paginated. Filters: `search`, `language` (ar/en), `type`, `price` (free/paid), `teacher`, `sort=rating` (highest rated first), `page`. Each course carries `rating` {average, count} |
| GET | `/api/v1/courses/{slug}` | — | Course with its curriculum (no lesson content), plus the user's enrollment and completed lessons |
| POST | `/api/v1/courses/{slug}/enroll` | student | Enroll in a free course (403 with a message for paid courses) |
| GET | `/api/v1/me/courses` | user | The student's enrollments with progress |
| GET | `/api/v1/lessons/{id}` | — | Lesson content and embeddable video (free previews, enrolled students, teacher, admin) |
| POST | `/api/v1/lessons/{id}/progress` | enrolled student | Report `watched_seconds` (and the player's `duration_seconds`); completes at 90% |
| POST | `/api/v1/lessons/{id}/complete` | enrolled student | Mark the lesson complete |
| GET | `/api/v1/me/profile` | user | The current user's profile |
| POST | `/api/v1/me/profile` | student | Update `name`, `grade_level`, `parent_phone`, `avatar` (multipart), `remove_avatar` |
| GET | `/api/v1/teachers/{id}` | — | Public profile of an approved teacher with their published courses |
| GET | `/api/v1/assignments/{id}` | enrolled student, teacher, admin | Instructions, deadline, max score and the student's own submission (`file.url` is a 30-minute signed link) |
| POST | `/api/v1/assignments/{id}/submission` | enrolled student | Create or update the submission (multipart: `content`, `file`, `remove_file`); 403 once graded |
| GET | `/api/v1/submissions/{id}/file` | student, course teacher, admin | The submission file (token/session clients) |
| GET | `/api/v1/me/notifications` | user | Latest 20 notifications plus `meta.unread_count` |
| POST | `/api/v1/me/notifications/{id}/read` | user | Mark one as read |
| POST | `/api/v1/me/notifications/read-all` | user | Mark all as read |
| GET | `/api/v1/quizzes/{id}` | enrolled student, teacher, admin | Rules, the student's attempts, `can_start` and why not |
| POST | `/api/v1/quizzes/{id}/attempts` | enrolled student | Start an attempt, or resume the open one (201 / 200) |
| GET | `/api/v1/attempts/{id}` | its student, course teacher, admin | Questions in the attempt's order (while open) or the result; correctness only when revealed |
| PUT | `/api/v1/attempts/{id}/answers` | its student, while open | Autosave `answers: [{question_id, option_id?, text_answer?}]` |
| POST | `/api/v1/attempts/{id}/submit` | its student, while open | Submit and auto-grade |
| GET | `/api/v1/me/grades` | student | Lesson and course grades per enrolled course, with done/total and live-session attendance |
| POST | `/api/v1/lessons/{id}/join` | enrolled student, teacher, admin | The meeting link, from 15 minutes before the start until the end (403 otherwise); records the student's attendance |
| GET | `/api/v1/me/live-sessions` | user | Upcoming and running live sessions in the student's courses |
| GET | `/api/v1/lessons/{id}/calendar.ics` | — | Calendar file for a live lesson of a published course (no meeting link) |
| PUT | `/api/v1/me/locale` | user | Save the interface language (`ar` / `en`) on the account, used for emails |
| GET | `/api/v1/lessons/{id}/comments` | enrolled student, teacher, admin | The lesson's questions with their replies, newest first (`filter=unresolved|mine`), plus `can_ask` |
| POST | `/api/v1/lessons/{id}/comments` | enrolled student | Ask a question |
| POST | `/api/v1/comments/{id}/replies` | enrolled student, teacher, admin | Reply to a question |
| PATCH / DELETE | `/api/v1/comments/{id}` | author (edit within 15 min); author, teacher or admin (delete) | Edit or delete a post |
| POST | `/api/v1/comments/{id}/resolve` · `/reopen` | asker, teacher, admin | Mark a question resolved or open again |
| POST | `/api/v1/comments/{id}/accept` | asker, teacher, admin | Accept a reply as the answer (resolves the question) |
| GET | `/api/v1/courses/{slug}/reviews` | — | Visible reviews with the average and a star breakdown |
| GET / PUT / DELETE | `/api/v1/courses/{slug}/review` | student | My review, and whether I can write one; write or rewrite it (enrolled, 30% done); delete it |

## Git conventions

[Conventional Commits](https://www.conventionalcommits.org), with a scope for the part of the repo that changed:

- `feat(api)`, `fix(api)`, `test(api)`: Laravel API
- `feat(admin)`: Filament panels
- `feat(web)`, `fix(web)`: React app
- `chore(repo)`, `ci(repo)`, `docs`: repository-wide
