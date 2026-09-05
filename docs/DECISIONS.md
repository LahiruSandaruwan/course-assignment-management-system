# Architecture and Design Decisions

- **Laravel Version**: Laravel 13 was specified in the assignment but requires PHP 8.3+; this environment provides PHP 8.2.12, so Laravel 12 was used instead. Architecture and idioms remain consistent with modern Laravel (bootstrap/app.php-based config, etc.).
- **Assignment Status**: A `status` field (`draft` / `published`) was added to the `assignments` table. Although not explicitly required by the assignment spec, it is necessary to control student visibility cleanly at the database and policy level. Students can only see published assignments.
