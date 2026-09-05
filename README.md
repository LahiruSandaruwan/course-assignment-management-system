# Course Assignment Management System

A comprehensive Learning Management System (LMS) built with a modern Laravel API and a Vue 3 Single Page Application frontend.

## 1. Project Title & Overview
**Course Assignment Management System** is built with **Laravel 12 API** (PHP 8.2 compatible) and **Vue 3.5.42** (Composition API, Pinia, Vue Router).

### Architectural Highlights:
- **Monorepo**: Both the Laravel backend and the Vue frontend (in the `frontend` directory) live in a unified repository.
- **Thin Controllers & Form Requests**: Validation logic is extracted into Form Requests, keeping controllers clean.
- **Policies & Services**: Business logic is encapsulated in Service classes (`CourseService`, `GradingService`), and authorization strictly resides in Policies.
- **Concurrency-Safe Transactions**: Uses database pessimistic locking to safely process concurrent submissions and grading actions.
- **Strict IDOR Prevention**: Robust authorization boundaries ensure students can only view their own data and enrolled courses, while instructors are limited to their own courses.

---

## 2. Environment & Setup Instructions

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+ and npm
- MySQL / SQLite

### Backend Setup
1. Install dependencies:
   ```bash
   composer install
   ```
2. Prepare your environment file:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. Run migrations and seed the database (this will configure test credentials):
   ```bash
   php artisan migrate --seed
   ```

**Pre-Seeded Test Credentials:**
The database seeding process creates three distinct test users with password `password`:
- **Admin**: `admin@example.com`
- **Instructor**: `instructor@example.com`
- **Student**: `student@example.com`

### Frontend Setup
1. Navigate to the frontend directory and install dependencies:
   ```bash
   cd frontend
   npm install --legacy-peer-deps
   ```
2. Prepare the frontend environment file:
   ```bash
   cp .env.example .env
   ```
3. Run the development server:
   ```bash
   npm run dev
   ```
   *(The frontend will be available at `http://localhost:5173`, connecting to the API at `http://localhost:8000/api`)*

### Test Suites
To verify code integrity, run the automated test suites:
- **Backend (Pest)**: 
  ```bash
  php artisan test
  ```
- **Frontend (Vitest)**: 
  ```bash
  cd frontend && npm run test
  ```

---

## 3. Database Architecture & Integrity Decisions

### Key Tables
- **`users`**: Stores user authentication data and roles (`admin`, `instructor`, `student`).
- **`courses`**: Maintained by instructors. Includes lifecycle `status` (active, archived).
- **`course_user`**: Pivot table handling student enrollments.
- **`assignments`**: Tied to courses. Includes a `status` (draft, published) to control visibility.
- **`submissions`**: Tied to assignments and students. Stores the submission text, instructor feedback, and numeric score.

### Integrity & Indexing
- **Composite Unique Constraints**:
  - `course_user (course_id, user_id)` guarantees a student cannot be enrolled in the same course twice.
  - `submissions (assignment_id, student_id)` ensures only a single submission row exists per student per assignment (subsequent submissions update the existing row).
- **Foreign Key Cascades**: Explicit `cascadeOnDelete()` rules ensure orphaned records (e.g., assignments of a deleted course) are automatically removed.
- **Database Indexing**: Critical columns frequently used in `WHERE` and `ORDER BY` clauses are indexed, specifically `instructor_id`, `status`, and `due_date`.
- **Archived Course Business Logic**: When a course is marked as `archived`, it becomes read-only. Enrolled students and instructors can still view historical grades and assignments, but all new submission attempts are blocked by backend policies.

---

## 4. Concurrency Challenge Answer

### Preventing Race Conditions
When multiple actors attempt conflicting modifications—such as two instructors simultaneously grading the same submission, or a student double-clicking the submit button—the system utilizes **Pessimistic Locking** to prevent lost updates or race conditions.

Within `GradingService`, the system wraps the grade operation in a database transaction:
```php
DB::transaction(function () use ($submission, $score, $feedback) {
    // Acquire a row-level lock
    $lockedSubmission = Submission::where('id', $submission->id)
        ->lockForUpdate()
        ->firstOrFail();

    // Perform the grade update...
});
```
`lockForUpdate()` forces any concurrent transaction trying to read or write this specific row to wait until the first transaction completes. 

Furthermore, **unique constraints** on the database level (e.g., the `assignment_id` and `student_id` constraint on `submissions`) act as the final backstop. If a race condition bypasses application logic, the database engine will throw a unique constraint violation exception, which the backend catches and gracefully converts into a clean HTTP 409 Conflict response.

---

## 5. Advanced Backend Task: N+1 Analysis & Optimization

### The Problem Code
```php
$courses = Course::all();
foreach ($courses as $course) {
    echo $course->instructor->name;
    echo $course->students->count();
    echo $course->assignments->count();
}
```

### Analysis & Answers
1. **What performance problem occurs?**
   This script triggers a severe **N+1 query problem**. It also unnecessarily loads the entirety of the `students` and `assignments` collections into PHP memory simply to count them.

2. **Why does it happen?**
   Because relationships are loaded lazily. The first `Course::all()` executes 1 query. Then, for every $N$ courses in the loop, the ORM executes:
   - 1 query to fetch the `instructor`
   - 1 query to fetch all `students`
   - 1 query to fetch all `assignments`
   Total queries executed: **1 + 3N**. For 50 courses, that is 151 database queries.

3. **How to improve it?**
   Utilize **eager loading** for relationships and **aggregate query functions** for counts:
   ```php
   $courses = Course::with('instructor:id,name')
                    ->withCount(['students', 'assignments'])
                    ->paginate(15);
   ```
   This reduces the query count to a constant **2 queries** (1 for the courses+counts, 1 for the instructors) regardless of how many courses are fetched.

4. **Scale to 100,000 courses?**
   At 100,000 courses, executing `Course::all()` will crash the application due to PHP memory exhaustion (`Allowed memory size exhausted`). 
   To scale, the system must use **Chunking** or **Cursor Pagination** to process courses in manageable batches (e.g., `Course::cursorPaginate(100)`). Furthermore, database tables must have indexes applied to `instructor_id` to speed up relational mapping, and columns should be aggressively pruned (e.g., `select('id', 'name')`) so only necessary data moves over the network. 

---

## 6. Architecture Question: Decoupled Notification System

To support multiple notification channels (Email, Push, Microsoft Teams) without modifying `GradingController` or `GradingService`, the system leverages the **Observer Pattern** and **SOLID Open/Closed Principle**.

1. **Event Dispatching**: Once grading successfully commits, `GradingService` simply fires a domain event: `event(new SubmissionGraded($submission));`. It doesn't know or care how notifications are sent.
2. **Event Listeners**: A queued listener (`SendSubmissionGradedNotification`) catches this event. It implements `ShouldQueue` and `ShouldQueueAfterCommit`, so it only runs after the grading transaction has actually committed — a rolled-back grade can never trigger a notification.
3. **Notification Channels via an Interface**: Rather than Laravel's built-in `Notification`/`via()` mechanism, this project defines its own `App\Notifications\Contracts\NotificationChannel` interface with a single method, `send(Submission $submission): void`. The listener resolves an implementation from the container (`app(NotificationChannel::class)`) instead of depending on a concrete class. Today, exactly one implementation exists — `LogNotificationChannel`, bound in `AppServiceProvider` — which simply logs the grading event as a stand-in for a real integration.
4. **Adding Email, Push, and Teams**: Six months later, each new channel becomes its own class implementing `NotificationChannel` (e.g. `EmailNotificationChannel`, `PushNotificationChannel`, `TeamsNotificationChannel`), with no changes to `GradingService` or `GradingController`. To notify through multiple channels at once, the listener would depend on an array of `NotificationChannel` implementations (bound via a tagged container binding) and loop over them, rather than resolving a single instance.
5. **Async Processing**: Because the listener is queued, slow third-party API calls (like Microsoft Teams) run in a background worker and never block the HTTP response sent back to the instructor.

---

## 7. Trade-offs, Known Limitations & Future Improvements

### Technical Decisions Logged
- **Laravel Version Compatibility**: The spec called for Laravel 13, but due to a strict PHP 8.2.12 environment constraint, the project was seamlessly adapted to Laravel 12 while maintaining modern architectural idioms.
- **Frontend Authentication**: Opted for a token-based (Bearer) mechanism persisted in `localStorage`. While Laravel Sanctum's stateful cookie approach provides superior XSS protection, the Bearer token approach eliminated CORS friction between decoupled local dev servers (`localhost:5173` vs `localhost:8000`), suiting the immediate scope of this assignment.
- **Git branching strategy**: This project used a trunk-based, linear commit history directly on `master` rather than feature branches. As a solo take-home assignment with no concurrent contributors, branches would have added process overhead without their usual benefit (isolating in-progress work from teammates), so the priority was a clear, atomic, easy-to-review commit-by-commit progression instead. In a team environment, this would switch to a feature-branch workflow (e.g. GitHub Flow: a short-lived branch per feature/fix, merged via pull request with review) to protect `main` and enable parallel work.

### Known Limitations
- No CI/CD pipeline configured (local verification via Pest and Vitest).
- JavaScript/Vue without TypeScript.
- Notification system uses a single Log-based reference channel (Email/Push/Teams are architectural demonstrations via the interface).
- True multi-threaded HTTP concurrency cannot be executed in synchronous Pest test runs (addressed via pessimistic database locking and unique constraint transaction handling).

### Future Improvements
Given more time, the system would benefit from:
- **Redis Queue Workers**: Transitioning from synchronous or database queues to Redis for highly performant, distributed background job processing.
- **Dockerization**: A complete `docker-compose.yml` to standardize the PHP/Node/MySQL environments across all developer machines.
- **OpenAPI/Swagger Specs**: Auto-generated API documentation for easier third-party integration and frontend developer reference.
- **Audit Logging**: A robust activity log tracking every role change, grading adjustment, and enrollment event for strict institutional compliance.

---

## 8. API Overview

The backend exposes a RESTful API heavily protected by Laravel Sanctum token authentication. 

### Key Endpoints:
- `POST /api/login` - Authenticates user and returns Bearer token.
- `GET /api/me` - Returns the authenticated user's profile and role.
- `GET /api/courses` - Returns a paginated list of courses (filtered by active status for students, or ownership for instructors).
- `GET /api/courses/{course}` - Returns detailed course information.
- `GET /api/courses/{course}/assignments` - Returns assignments for a specific course.
- `GET /api/courses/{course}/students` - Returns enrolled students (Instructor/Admin only).
- `GET /api/assignments/{assignment}` - Returns assignment details.
- `GET /api/assignments/{assignment}/my-submission` - Returns the authenticated student's submission for the assignment.
- `POST /api/assignments/{assignment}/submissions` - Creates or updates a student's submission (Upsert logic).
- `GET /api/assignments/{assignment}/submissions` - Returns a paginated list of all submissions (Instructor/Admin only).
- `PUT /api/submissions/{submission}/grade` - Submits a grade and feedback (Instructor/Admin only), utilizing pessimistic locking.
