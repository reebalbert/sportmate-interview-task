
# GitHub Repository Synchronization

A Laravel and Vue application for synchronizing public GitHub repositories belonging to users and organizations.

The application allows synchronization targets to be added, synchronizes their repositories through the GitHub API using queued jobs, and stores repository data locally for browsing, searching, and filtering.

## Features

- Add GitHub users and organizations as synchronization targets
- Prevent duplicate synchronization targets
- Synchronize repositories asynchronously using Laravel queues
- Support both GitHub user and organization repositories
- Store and update repository data locally
- Track synchronization status and history
- Store the last successful synchronization time
- Store and display synchronization errors
- Retry failed synchronization jobs
- Retry temporary synchronization failures with backoff
- Prevent overlapping synchronization jobs for the same target
- View and retry failed queue jobs
- Search repositories by name, description, and README content
- Filter repositories by programming language
- Paginate repository results
- Automatically refresh the UI while synchronization is running
- Handle GitHub API pagination for targets with more than 100 repositories
- Periodically synchronize configured targets using Laravel's scheduler
- Access synchronization targets and repositories through a versioned REST API
- Authenticate REST API requests using Laravel Sanctum bearer tokens
- Create synchronization targets and trigger synchronization through the REST API
- Retrieve paginated repository results through the REST API

## Tech Stack

- PHP
- Laravel
- Laravel Sanctum
- Vue 3
- Inertia.js
- TypeScript
- Tailwind CSS
- MySQL
- Laravel Queues
- Laravel Scheduler
- GitHub REST API
- PHPUnit

## Installation

Clone the repository and install the dependencies:

```bash
composer install
npm install
```

Create the environment file:

```bash
cp .env.example .env
php artisan key:generate
```

Configure the database connection in `.env`, then run:

```bash
php artisan migrate
```

Start the application:

```bash
composer run dev
```

The application uses queued jobs for repository synchronization. Make sure a queue worker is running:

```bash
php artisan queue:work
```

For local development, scheduled synchronization can be run with:

```bash
php artisan schedule:work
```

In production, Laravel's scheduler should be triggered every minute:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## GitHub API

The application uses the public GitHub REST API.

The following environment variables are available:

```env
GITHUB_API_URL=https://api.github.com
GITHUB_TOKEN=
```

A GitHub token is optional for public repositories, but recommended to avoid the lower unauthenticated API rate limit.

## REST API

The application provides a versioned REST API for accessing synchronization targets, creating new targets, triggering synchronization, and retrieving synchronized repositories.

All `/api/v1` endpoints are protected using Laravel Sanctum bearer token authentication.

### Authentication

Generate an API token for an existing user using Laravel Tinker:

```bash
php artisan tinker
```

```php
$user = App\Models\User::firstOrFail();
$token = $user->createToken('SportMate API')->plainTextToken;
```

Include the generated token in the request headers:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

API tokens should be kept private and must not be committed to the repository.

### Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/sync-targets` | List synchronization targets |
| GET | `/api/v1/sync-targets/{id}` | Retrieve a specific synchronization target |
| POST | `/api/v1/sync-targets` | Create a synchronization target |
| POST | `/api/v1/sync-targets/{id}/sync` | Queue synchronization for a target |
| GET | `/api/v1/sync-targets/{id}/repositories` | Retrieve paginated repositories belonging to a target |

### Example Requests

Create a synchronization target:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/sync-targets \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"name":"laravel","type":"organization"}'
```

Retrieve synchronization targets:

```bash
curl http://127.0.0.1:8000/api/v1/sync-targets \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Queue synchronization:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/sync-targets/1/sync \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Retrieve repositories:

```bash
curl http://127.0.0.1:8000/api/v1/sync-targets/1/repositories \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Repository responses are paginated, with 20 records per page. Additional pages can be retrieved using the `page` query parameter.

Synchronization requests are processed asynchronously through Laravel queues.

The API uses standard HTTP status codes, including:

- `200 OK` for successful retrieval
- `201 Created` when a synchronization target is created
- `202 Accepted` when synchronization is queued
- `401 Unauthorized` for unauthenticated requests
- `404 Not Found` when a requested target does not exist
- `409 Conflict` when synchronization is already in progress
- `422 Unprocessable Entity` for validation errors

## Synchronization

Each synchronization target represents either a GitHub user or organization.

When synchronization is requested:

1. The target is marked as `syncing`.
2. A Laravel queue job is dispatched.
3. Repositories are fetched from the appropriate GitHub API endpoint.
4. Existing repositories are updated, new repositories are created, and repositories no longer returned by GitHub are removed.
5. Repository README content is retrieved through separate queued jobs.
6. The target is marked as `synced` and its last successful synchronization time is updated.
7. Temporary failures are retried automatically.
8. If all attempts fail, the target is marked as `failed` and the most recent error is stored.

Synchronization jobs are attempted up to three times, with a backoff between attempts.

GitHub API pagination is handled automatically using pages of up to 100 repositories.

Overlapping synchronization jobs for the same target are prevented through synchronization status checks and queue middleware.

In addition to manual synchronization, all configured synchronization targets are periodically queued for synchronization using Laravel's scheduler.

## Failed Jobs

Failed queue jobs can be viewed on the Failed Jobs page.

A failed job can be retried directly from the interface. Laravel's failed job storage is used to keep track of jobs that have exhausted their retry attempts.

## Testing

Run the automated test suite with:

```bash
php artisan optimize:clear
php artisan test
```

The test suite covers the main synchronization flow, including:

- Fetching repositories for GitHub users
- Fetching repositories for GitHub organizations
- GitHub API pagination
- Storing synchronized repositories
- Updating existing repositories without creating duplicates
- Synchronization failure handling
- Dispatching synchronization through the controller
- REST API authentication
- Retrieving synchronization targets through the API
- Creating synchronization targets through the API
- Rejecting duplicate synchronization target names
- Queuing synchronization through the API
- Retrieving repositories belonging to a specific synchronization target

API Feature tests can also be run separately:

```bash
php artisan test --filter=SyncTargetApiTest
```

Tests use an in-memory SQLite database and do not require the local MySQL database.

## Implementation Decisions

GitHub API communication is isolated in a dedicated `GitHubClient` service so external API concerns remain separate from controllers and synchronization logic.

Repository synchronization runs through Laravel's queue system rather than during the HTTP request. This keeps synchronization independent from the request lifecycle and avoids blocking the UI while GitHub repositories are being fetched.

Synchronization jobs use retry and backoff policies so temporary GitHub API failures do not immediately result in a permanently failed synchronization.

Repository records are updated using their GitHub repository ID, which provides a stable identifier and prevents duplicate repository records during subsequent synchronizations.

Repository README content is synchronized through separate queued jobs to keep the main repository synchronization process focused on repository metadata.

Repository searching supports name, description, and README content. MySQL full-text search is used for searching repository data.

The frontend polls for updated target data only while a synchronization is running. Polling stops once no target is in the `syncing` state.

The REST API uses Laravel Sanctum for token-based authentication and API Resources for consistent JSON responses. API controllers reuse existing validation and synchronization services to avoid duplicating application logic.

The API is versioned under `/api/v1` to allow future changes without breaking existing clients.

## Trade-offs and Possible Improvements

Given more time, I would consider:

- Handling GitHub API rate limits more explicitly
- Adding more integration and UI tests
- Extending REST API filtering and search capabilities
- Using events or WebSockets instead of polling if real-time synchronization updates became important
- Restricting operational pages such as Failed Jobs if the application were exposed publicly
- Introducing more granular API permissions if different user roles were required

The current implementation focuses on keeping the synchronization flow simple while covering the main requirements of the assignment and selected optional features.

## AI Usage

AI-assisted development tools were used during the implementation of this assignment.

Details are documented in [AI_USAGE.md](AI_USAGE.md).
