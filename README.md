# Enterprise-ready PHP API Skeleton

A production-focused REST API skeleton built with PHP 8, Composer, MySQL (via PDO), and JWT authentication. The project embraces PSR-4 autoloading, structured application layers, centralized configuration, request validation, and Monolog logging to provide an enterprise foundation for user management, role-based access control, and project delivery workflows.

## Features

- ✅ Structured application bootstrap with environment isolation and centralized configuration
- ✅ JWT-based authentication with configurable TTL, issuer, and enriched login context
- ✅ MySQL-first persistence via PDO with automatic schema migrations and seed data
- ✅ Comprehensive RBAC with roles, permissions, group assignments, and user context aggregation
- ✅ Project portfolio management with timeline auditing, membership tracking, and task orchestration
- ✅ Centralized JSON request/response handling and validation utilities
- ✅ Route-level middleware pipeline with bearer-token enforcement
- ✅ Structured file-based logging ready for rotation and external aggregation
- ✅ Composer-managed dependencies and PSR-4 autoloading

## Requirements

- PHP 8.1 or newer with `pdo_mysql`, `json`, and `openssl` extensions enabled (`pdo_sqlite` optional for local testing)
- MySQL 8.x (or compatible) database server
- Composer 2.x

## Getting started

1. **Install dependencies**

   ```bash
   composer install
   ```

2. **Prepare environment configuration**

   ```bash
   cp .env.example .env
   ```

   Update the `.env` file with secure values (database credentials, JWT secret, etc.). The sample configuration targets a MySQL database named `php_enterprise_api` on `127.0.0.1:3306`.

3. **Provision database**

   ```sql
   CREATE DATABASE php_enterprise_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'php_api'@'%' IDENTIFIED BY 'secret';
   GRANT ALL PRIVILEGES ON php_enterprise_api.* TO 'php_api'@'%';
   FLUSH PRIVILEGES;
   ```

4. **Serve the API**

   ```bash
   php -S 0.0.0.0:8000 -t public
   ```

   The entry point (`public/index.php`) bootstraps the framework, loads environment variables, runs database migrations, seeds RBAC data, and dispatches the incoming request through the router and middleware pipeline.

## Project structure

```
├── public/              # HTTP entry point (front controller)
├── routes/              # Route declarations
├── src/
│   ├── Bootstrap/       # Environment bootstrap
│   ├── Config/          # Configuration aggregator
│   ├── Controller/      # HTTP controllers (auth, users, RBAC, projects, tasks)
│   ├── Database/        # Connection factory and migrations
│   ├── Http/            # Request/response primitives and exceptions
│   ├── Logging/         # Logger factory
│   ├── Middleware/      # Route middleware contracts & implementations
│   ├── Repository/      # Persistence layer for each aggregate
│   ├── Routing/         # Lightweight router & route model
│   ├── Security/        # JWT + password hashing utilities
│   └── Service/         # Application services / business logic
├── storage/
│   └── logs/            # Monolog output (gitignored)
├── .env.example         # Sample environment configuration
└── composer.json        # Composer metadata and PSR-4 autoload rules
```

## Environment variables

| Variable        | Description                                                                 | Default                    |
| --------------- | --------------------------------------------------------------------------- | -------------------------- |
| `APP_NAME`      | Application name used in logs and telemetry                                 | `PHP Enterprise API`       |
| `APP_ENV`       | Environment label (`production`, `staging`, `local`, …)                     | `production`               |
| `APP_DEBUG`     | Enables verbose error output when set to `true`                             | `false`                    |
| `APP_URL`       | Base URL / issuer used for JWT tokens                                       | `http://localhost`         |
| `DB_DRIVER`     | Database driver (`mysql` or `sqlite`)                                       | `mysql`                    |
| `DB_HOST`       | MySQL host                                                                  | `127.0.0.1`                |
| `DB_PORT`       | MySQL port                                                                  | `3306`                     |
| `DB_DATABASE`   | MySQL database name                                                         | `php_enterprise_api`       |
| `DB_USERNAME`   | MySQL username                                                              | `php_api`                  |
| `DB_PASSWORD`   | MySQL password                                                              | `secret`                   |
| `DB_CHARSET`    | Database charset                                                            | `utf8mb4`                  |
| `DB_TIMEZONE`   | Database session timezone                                                   | `+00:00`                   |
| `JWT_SECRET`    | Secret key used to sign JWTs – **must be replaced in production**           | `please-change-me-super-secret-key` |
| `JWT_TTL`       | Token lifetime in seconds                                                   | `3600`                     |

## API overview

All endpoints accept and return JSON. Authentication-protected routes require an `Authorization: Bearer <token>` header.

### Core authentication & users

| Method | Path                                   | Description                                | Auth |
| ------ | -------------------------------------- | ------------------------------------------ | ---- |
| POST   | `/api/v1/auth/register`                | Register a new account (auto role assign)  | ❌   |
| POST   | `/api/v1/auth/login`                   | Obtain a JWT access token + user context   | ❌   |
| POST   | `/api/v1/auth/logout`                  | Hint clients to discard the token          | ✅   |
| GET    | `/api/v1/users`                        | List users with roles, permissions, groups | ✅   |
| POST   | `/api/v1/users`                        | Create a user                              | ✅   |
| GET    | `/api/v1/users/{id}`                   | Retrieve user by ID                        | ✅   |
| PUT    | `/api/v1/users/{id}`                   | Update user attributes                     | ✅   |
| DELETE | `/api/v1/users/{id}`                   | Delete a user                              | ✅   |
| GET    | `/api/v1/users/by-email/{email}`       | Retrieve user by email                     | ✅   |
| GET    | `/api/v1/users/by-username/{username}` | Retrieve user by username                  | ✅   |

### Roles & permissions

| Method | Path                                   | Description                             |
| ------ | -------------------------------------- | --------------------------------------- |
| GET    | `/api/v1/roles`                        | List roles with permissions             |
| POST   | `/api/v1/roles`                        | Create role                             |
| GET    | `/api/v1/roles/{id}`                   | Retrieve role                           |
| PUT    | `/api/v1/roles/{id}`                   | Update role                             |
| DELETE | `/api/v1/roles/{id}`                   | Delete role                             |
| POST   | `/api/v1/roles/{id}/permissions`       | Synchronize role permissions            |
| POST   | `/api/v1/roles/{id}/assign`            | Assign role to user                     |
| POST   | `/api/v1/roles/{id}/revoke`            | Revoke role from user                   |
| GET    | `/api/v1/permissions`                  | List permissions                        |
| POST   | `/api/v1/permissions`                  | Create permission                       |
| GET    | `/api/v1/permissions/{id}`             | Retrieve permission                     |
| PUT    | `/api/v1/permissions/{id}`             | Update permission                       |
| DELETE | `/api/v1/permissions/{id}`             | Delete permission                       |

### Groups, projects & tasks

| Method | Path                                                | Description                                      |
| ------ | --------------------------------------------------- | ------------------------------------------------ |
| GET    | `/api/v1/groups`                                    | List collaboration groups with members           |
| POST   | `/api/v1/groups`                                    | Create group                                     |
| GET    | `/api/v1/groups/{id}`                               | Retrieve group                                   |
| PUT    | `/api/v1/groups/{id}`                               | Update group                                     |
| DELETE | `/api/v1/groups/{id}`                               | Delete group                                     |
| GET    | `/api/v1/groups/{id}/members`                       | List group members                               |
| POST   | `/api/v1/groups/{id}/members`                       | Add member to group                              |
| DELETE | `/api/v1/groups/{id}/members/{userId}`              | Remove member from group                         |
| GET    | `/api/v1/projects`                                  | List projects with members, timeline, tasks      |
| POST   | `/api/v1/projects`                                  | Create project                                   |
| GET    | `/api/v1/projects/{id}`                             | Retrieve project                                 |
| PUT    | `/api/v1/projects/{id}`                             | Update project                                   |
| DELETE | `/api/v1/projects/{id}`                             | Delete project                                   |
| POST   | `/api/v1/projects/{id}/members`                     | Add project member                               |
| DELETE | `/api/v1/projects/{id}/members/{userId}`            | Remove project member                            |
| GET    | `/api/v1/projects/{id}/timeline`                    | List timeline events                             |
| POST   | `/api/v1/projects/{id}/timeline`                    | Record timeline event                            |
| GET    | `/api/v1/projects/{projectId}/tasks`                | List tasks for project                           |
| POST   | `/api/v1/projects/{projectId}/tasks`                | Create task for project                          |
| GET    | `/api/v1/tasks/{id}`                                | Retrieve task                                    |
| PUT    | `/api/v1/tasks/{id}`                                | Update task                                      |
| DELETE | `/api/v1/tasks/{id}`                                | Delete task                                      |

## Example request – create project

```bash
curl -X POST http://localhost:8000/api/v1/projects \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "name": "Enterprise Rollout",
    "description": "Global deployment program",
    "status": "planned",
    "owner_id": 1,
    "group_id": 1,
    "start_date": "2024-01-01",
    "end_date": "2024-06-30"
  }'
```

## Testing

Add a `tests/` directory and install your preferred testing framework (e.g. PHPUnit) to automate regression checks:

```bash
composer require --dev phpunit/phpunit
vendor/bin/phpunit
```

## Deployment notes

- Ensure the MySQL user has the necessary privileges and that migrations run with appropriate permissions.
- Rotate the JWT secret regularly and consider asymmetric signing for stricter compliance.
- Behind a reverse proxy, terminate TLS and forward the scheme to maintain accurate issuer metadata.
- Use a process manager (e.g. `php-fpm` behind Nginx or Apache) for real production deployments.

## License

Released under the [MIT License](LICENSE).
