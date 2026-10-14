# GitHub Repository Synchronization

A Laravel and Vue application for synchronizing public GitHub repositories belonging to users and organizations.

The application allows synchronization targets to be added, synchronizes their repositories through the GitHub REST API using queued jobs, and stores repository data locally for browsing, searching, and filtering.

## Features

- Add GitHub users and organizations as synchronization targets
- Prevent duplicate synchronization targets
- Synchronize repositories asynchronously using Laravel queues
- Store, update, and remove repository data locally
- Track synchronization status, last successful synchronization time, and errors
- Retry temporary synchronization failures with backoff
- Prevent overlapping synchronization jobs for the same target
- View and retry failed queue jobs
- Search repositories by name, description, and README content
- Filter repositories by programming language
- Paginate repository results
- Display repository metadata, including GitHub's last-updated timestamp
- Refresh synchronization status through polling
- Handle GitHub API pagination for targets with more than 100 repositories
- Periodically synchronize configured targets using Laravel's scheduler
- Access synchronization targets and repositories through a versioned REST API
- Authenticate REST API requests using Laravel Sanctum bearer tokens

## Tech Stack

- PHP / Laravel
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

Create the environment file and generate the application key:

```bash
cp .env.example .env
php artisan key:generate
```

Configure the database connection in `.env`, then run the migrations:

```bash
php artisan migrate
```

Start the development environment:

```bash
php artisan serve
```

Alternatively, compile the frontend assets for production:

```bash
npm run dev
```

### Queue Worker

Repository synchronization is processed asynchronously using Laravel queues.

Make sure the queue connection is configured in .env and start a queue worker:

```bash
php artisan queue:work
```

### Scheduler

For local development, scheduled synchronization can be executed using:

```bash
php artisan schedule:work
```

## GitHub API

The application communicates with the public GitHub REST API through a dedicated `GitHubClient` service.

The following environment variables are available:

```env
GITHUB_API_URL=https://api.github.com
GITHUB_TOKEN=
```

A GitHub token is optional when accessing public repositories, but recommended because unauthenticated requests are subject to lower API rate limits.

GitHub users and organizations are handled through their respective API endpoints.

Repository pagination is handled automatically using pages of up to 100 repositories. The full paginated result should be obtained before reconciling local records; otherwise a partial result could incorrectly mark repositories as missing.

GitHub applies API rate limits. An optional token helps increase the available request quota, but does not eliminate rate limits. The current implementation does not provide advanced handling of `X-RateLimit-Remaining`, `X-RateLimit-Reset`, or secondary rate limits; rate-limit-aware retry scheduling and monitoring are deferred improvements. Repeated or partial API failures must not be treated as an authoritative empty repository list.

## REST API

The application provides a versioned REST API for accessing synchronization targets, creating new targets, triggering synchronization, and retrieving synchronized repositories.

The REST API endpoints under `/api/v1` require Laravel Sanctum bearer token authentication. The Vue/Inertia web interface is intentionally accessible without authentication, as user-based access control was not part of the implemented web workflow.

API authentication is separate from the web interface and does not provide user-based data isolation or role-specific permissions.

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
6. The target is marked as `synced`, and its last successful synchronization time is updated.
7. Temporary failures are retried automatically.
8. If all attempts fail, the target is marked as `failed`, and the most recent error is stored.

Synchronization jobs are attempted up to three times, with backoff between attempts.

GitHub API pagination is handled automatically using pages of up to 100 repositories.

Overlapping synchronization jobs for the same target are prevented through synchronization status checks and queue middleware.

### Queue Reliability and Concurrency

- **Duplicate requests:** Synchronization initiation checks the target's status so that a target already being synchronized is not queued again. Database row locking within a transaction helps serialize concurrent initiation requests.
- **Transaction boundary:** The status check and state transition run inside a short database transaction, and the synchronization job is dispatched with `afterCommit()`. External GitHub HTTP calls and repository processing run in queued jobs, outside the initiation transaction.
- **Overlapping jobs:** Laravel's `WithoutOverlapping` middleware uses a target-specific cache lock to prevent concurrent execution for the same target. Different targets can be processed independently.
- **Retries and backoff:** The repository synchronization job is configured for up to three attempts with backoff. Lock-contention releases may also consume attempts, so the retry and overlap settings should be tested together.
- **Timeouts:** The synchronization job has a 60-second timeout. The queue worker timeout, HTTP client timeouts, lock expiration, and `retry_after` configuration must be coordinated for the selected deployment environment.
- **Failed jobs:** Exhausted jobs can be inspected and retried through the Failed Jobs page; synchronization failures are also recorded on the corresponding target.

These safeguards cover different failure points. The status/transaction check prevents duplicate initiation, while the queue middleware protects execution even if multiple jobs are already queued.

In addition to manual synchronization, configured synchronization targets are periodically queued for synchronization using Laravel's scheduler.

### Synchronization Status

The application distinguishes between the following states:

- `never_synced` — synchronization has not yet been performed
- `syncing` — synchronization is currently in progress
- `synced` — the most recent synchronization completed successfully
- `failed` — synchronization failed

The user interface displays the synchronization status and the last successful synchronization time.

Repository records also store GitHub's own last-updated timestamp, which is separate from the application's synchronization time.

The frontend uses polling to refresh synchronization data without requiring manual page reloads.

## Repository Search and Filtering

Synchronized repositories can be searched by name, description, and README content.

Repository searching uses MySQL full-text search for the supported text fields.

The user interface also provides programming-language filtering and pagination.

Search and filtering operate on locally stored repository data rather than making a new GitHub API request for every user interaction.

## Failed Jobs

Failed queue jobs can be viewed on the Failed Jobs page.

A failed job can be retried directly from the interface. Laravel's failed job storage is used to keep track of jobs that have exhausted their retry attempts.

The application also stores synchronization errors against the corresponding target, allowing users to identify failed synchronizations without exposing raw exception details.

## Testing

Run the automated test suite with:

```bash
php artisan optimize:clear
php artisan test
```

The test suite includes coverage for:

- Fetching repositories for GitHub users and organizations
- GitHub API pagination
- Storing synchronized repositories
- Updating existing repositories without creating duplicates
- Synchronization failure handling
- README content synchronization and failure handling
- Synchronization logging and statistics
- Preventing duplicate synchronization requests
- Dispatching synchronization through the controller
- Scheduled synchronization handling
- REST API authentication
- Retrieving and creating synchronization targets through the API
- Rejecting duplicate synchronization target names
- Queuing synchronization through the API
- Retrieving repositories belonging to a specific synchronization target

API Feature tests can also be run separately:

```bash
php artisan test --filter=SyncTargetApiTest
```

Tests use an in-memory SQLite database and do not require the local MySQL database.

External GitHub API requests are mocked or faked in automated tests to keep the tests independent of GitHub availability and rate limits. The test environment uses SQLite as configured in `phpunit.xml`, so the PHP SQLite/PDO SQLite extension must be enabled. MySQL-specific FULLTEXT behavior should also be verified against MySQL because SQLite does not reproduce all MySQL search semantics.

### Planned Manual and Additional Automated Tests

Manual functional testing is documented separately in the technical report. The planned checks include target creation and validation, duplicate rejection, manual and scheduled synchronization, repeated sync without duplicates, updates and reconciliation, README handling, search/filtering/pagination, synchronization logs, and error states.

Further automated coverage should prioritize concurrent initiation requests, overlapping jobs and retry exhaustion, timeout handling, incomplete GitHub pagination, reconciliation safety after API failures, API authorization boundaries, and browser-level UI interactions. These are planned checks, not claims that every scenario already has an automated test.

## Implementation Decisions

### Application Structure

GitHub API communication is isolated in a dedicated `GitHubClient` service, keeping external API concerns separate from controllers and synchronization logic.

Controllers handle HTTP requests and responses, while synchronization logic is delegated to the appropriate services and queue jobs.

This separation makes the integration easier to test and allows the same synchronization functionality to be reused from different entry points.

### Asynchronous Processing

Repository synchronization runs through Laravel's queue system rather than during the HTTP request.

This avoids blocking the user interface while repositories are being fetched and allows synchronization failures to be retried independently.

Synchronization jobs use retry and backoff policies to handle temporary GitHub API failures.

### Repository Persistence

Repository records are identified using their GitHub repository IDs, which provide stable external identifiers.

Existing records are updated during subsequent synchronizations instead of creating duplicates.

Repositories no longer returned by GitHub are removed during reconciliation.

Repository README content is synchronized through separate queued jobs to keep the main repository synchronization process focused on repository metadata.

### Database Constraints and Indexing

Database constraints are used to protect data integrity independently of application-level validation.

Important considerations include:

- **Synchronization target name:** a unique constraint prevents duplicate targets.
- **GitHub repository ID:** a unique constraint prevents duplicate repository records during repeated synchronizations.
- **Synchronization target foreign key:** supports the relationship between repositories and their synchronization target.
- **Repository text fields:** a MySQL FULLTEXT index supports searching repository names, descriptions, and README content.

### Frontend

The frontend is implemented using Vue 3, TypeScript, and Inertia.js.

Inertia allows Laravel controllers to provide data directly to Vue pages without requiring a separate REST API for the web interface.

The interface displays synchronization targets, their statuses, repository statistics, and synchronized repository records.

Polling is used to refresh synchronization data. A WebSocket-based solution was not necessary for the scope of this assignment.

### REST API

The REST API uses Laravel Sanctum for token-based authentication and API Resources for consistent JSON responses.

API controllers reuse existing validation and synchronization services to avoid duplicating application logic.

The API is versioned under `/api/v1` to allow future changes without breaking existing clients.

### Authentication and Authorization Scope

Sanctum bearer tokens authenticate API requests. The current design does not implement per-user ownership of synchronization targets, tenant isolation, or granular token abilities/role-based authorization. Consequently, authentication should not be mistaken for resource-level authorization: a production multi-user deployment would need policies, ownership checks, and restrictions on operational endpoints before exposing the application to untrusted users.

## Trade-offs and Possible Improvements

The web interface, including synchronization management and the Failed Jobs page, is intentionally accessible without authentication. This keeps the assignment focused on repository synchronization and its related functionality.

This is a conscious security trade-off rather than a production-ready access control strategy. In a production environment, synchronization operations and especially failed job inspection and retry actions should be restricted to authorized users or administrators.

The implementation prioritizes a complete repository synchronization workflow, clear separation of responsibilities, and a usable interface. The following enhancements would be the next steps if the application were developed further:

- **GitHub rate-limit handling:** Inspect rate-limit response headers, distinguish temporary failures from permanent errors, and schedule retries based on the API's reset time.
- **Broader test coverage:** Add more edge-case and integration tests, including reconciliation after partial API failures, concurrency, retry exhaustion, and frontend interactions.
- **REST API capabilities:** Extend repository endpoints with server-side search, language filtering, and additional query options.
- **Access control:** Restrict operational functionality such as the Failed Jobs page and introduce user-level ownership of synchronization targets for a multi-user deployment.
- **More granular API authorization:** Define token abilities or role-based permissions if different kinds of API consumers are introduced.
- **Real-time updates:** Consider events and WebSockets if polling becomes inefficient or immediate synchronization feedback is required.

These items are future improvements, not claims of functionality already implemented.

## Assignment Summary

### Approximate Time Spent

**More than 8 hours.** I spent additional time beyond the initial implementation on architecture, optional functionality, testing, documentation, and refining the user interface. I have not recorded an exact total, so this is an approximate statement rather than a precise time log.

### Completed Parts

- GitHub user and organization synchronization targets, with validation and duplicate prevention.
- Dedicated `GitHubClient` integration for retrieving public repository data, including paginated GitHub API responses.
- Local repository creation, updating, duplicate prevention through database constraints, and reconciliation of repositories no longer returned by GitHub.
- Asynchronous synchronization using Laravel queues, including status tracking, retries, backoff, and protection against overlapping jobs.
- Separate queued jobs for repository README synchronization.
- Manual synchronization and scheduled synchronization of configured targets.
- Synchronization logs, error reporting, and a page for viewing and retrying failed queue jobs.
- Vue 3 / Inertia.js interface with repository browsing, MySQL FULLTEXT search (including README content), language filtering, pagination, and status polling.
- Versioned `/api/v1` REST API protected by Laravel Sanctum bearer tokens.
- Automated tests with faked external HTTP requests, plus separate technical and AI-usage documentation.

### Incomplete or Deferred Parts

The main assignment workflow and the optional features listed above have been implemented. The following production-oriented or additional features were **not** implemented:

- Per-user ownership of synchronization targets or multi-tenant data isolation.
- Comprehensive frontend/end-to-end testing and additional concurrency and failure-recovery scenarios.
- Advanced GitHub rate-limit monitoring and rate-limit-aware retry scheduling.
- Server-side search and language filtering in the REST API.
- Granular API token permissions and role-based authorization.
- WebSocket-based real-time updates.

### What I Would Implement Next

My first priority would be to improve reliability and expand test coverage for GitHub API failures, concurrent synchronization, and retry scenarios. I would also enhance GitHub rate-limit handling and restrict access to operational pages, particularly the Failed Jobs page.

For a multi-user application, I would introduce user-level ownership and authorization for synchronization targets before extending the REST API further.

### Important Compromises and Shortcuts

- **Polling instead of WebSockets:** The interface periodically refreshes synchronization status. This approach is sufficient for the assignment, although it does not provide instant updates.
- **Shared synchronization targets:** Targets are not isolated by authenticated user. This simplifies the implementation but would require additional authorization for a multi-user deployment.
- **GitHub rate-limit handling:** The existing retry and backoff mechanisms handle temporary failures, but retries are not scheduled based on GitHub's rate-limit response headers.
- **Selective test coverage:** Automated tests cover the main backend and API functionality, but do not include comprehensive frontend testing or every possible failure scenario.

These were deliberate scope decisions rather than unfinished core functionality.

## AI Usage

AI-assisted development tools were used during the implementation of this assignment.

The tools, important instructions, substantially AI-assisted areas, modified or rejected suggestions, and validation approach are documented separately in [AI_USAGE.md](AI_USAGE.md).