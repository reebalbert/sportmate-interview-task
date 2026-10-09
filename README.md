# GitHub Repository Synchronization

A Laravel and Vue application for synchronizing public GitHub repositories belonging to users and organizations.

The application allows synchronization targets to be added, synchronizes their repositories through the GitHub API using queued jobs, and stores the repository data locally for browsing and filtering.

## Features

- Add GitHub users and organizations as synchronization targets
- Prevent duplicate synchronization targets
- Synchronize repositories asynchronously using Laravel queues
- Support both GitHub user and organization repositories
- Store and update repository data locally
- Track synchronization status
- Store the last successful synchronization time
- Store and display synchronization errors
- Retry failed synchronization jobs
- Retry temporary synchronization failures with backoff
- View failed queue jobs
- Search repositories by name and description
- Filter repositories by programming language
- Client-side repository pagination
- Automatic UI refresh while synchronization is running
- GitHub API pagination for targets with more than 100 repositories
- Periodically synchronize all configured targets using Laravel's scheduler

## Tech Stack

- PHP
- Laravel
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

## Synchronization

Each synchronization target represents either a GitHub user or organization.

When synchronization is requested:

1. The target is marked as `syncing`.
2. A Laravel queue job is dispatched.
3. Repositories are fetched from the appropriate GitHub API endpoint.
4. Existing repositories are updated and new repositories are created.
5. The target is marked as `synced` and its last successful synchronization time is updated.
6. Temporary failures are retried automatically.
7. If all attempts fail, the target is marked as `failed` and the most recent error is stored.

Synchronization jobs are attempted up to three times, with a backoff between attempts.

GitHub API pagination is handled automatically using pages of up to 100 repositories.

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

- fetching repositories for GitHub users;
- fetching repositories for GitHub organizations;
- GitHub API pagination;
- storing synchronized repositories;
- updating existing repositories without creating duplicates;
- synchronization failure handling;
- dispatching synchronization through the controller.

Tests use an in-memory SQLite database and do not require the local MySQL database.

## Implementation Decisions

GitHub API communication is isolated in a dedicated `GitHubClient` service so external API concerns remain separate from controllers and synchronization logic.

Repository synchronization runs through Laravel's queue system rather than during the HTTP request. This keeps synchronization independent from the request lifecycle and avoids blocking the UI while GitHub repositories are being fetched.

Synchronization jobs use retry and backoff policies so temporary GitHub API failures do not immediately result in a permanently failed synchronization.

Repository records are updated using their GitHub repository ID, which provides a stable identifier and prevents duplicate repository records during subsequent synchronizations.

Search, language filtering and repository pagination are handled client-side because repository data is already loaded for each synchronization target and the expected dataset is relatively small.

The frontend polls for updated target data only while a synchronization is running. Polling stops once no target is in the `syncing` state.

## Trade-offs and Possible Improvements

Given more time, I would consider:

- preventing overlapping synchronization jobs for the same target;
- handling GitHub API rate limits more explicitly;
- adding more integration and UI tests;
- moving repository filtering and pagination to the backend if the expected dataset became significantly larger;
- using events or WebSockets instead of polling if real-time synchronization updates became important;
- restricting operational pages such as Failed Jobs if the application were exposed publicly.

The current implementation focuses on keeping the synchronization flow simple while covering the main requirements of the assignment.

## AI Usage

AI-assisted development tools were used during the implementation of this assignment.

Details are documented in [AI_USAGE.md](AI_USAGE.md).
