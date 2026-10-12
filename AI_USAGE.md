
# AI Usage

AI tools were used as a development assistant during this assignment.

## Tools

- ChatGPT

## How AI was used

AI was primarily used for:

- discussing implementation and architectural decisions;
- reviewing Laravel and Vue code;
- debugging development and testing issues;
- suggesting test cases and edge cases;
- improving the user interface and user experience;
- reviewing the implementation against the assignment requirements;
- discussing synchronization reliability and concurrency handling;
- designing and testing a versioned REST API using Laravel Sanctum.

AI was used as an assistant rather than as an autonomous implementation tool. Suggested approaches and generated code were reviewed and adjusted before being added to the project.

## Important Prompts and Instructions

Examples of instructions and questions given to the AI included:

- how to structure synchronization targets and locally stored GitHub repositories;
- how to separate GitHub API communication from synchronization logic;
- how to organize synchronization initialization into a dedicated service;
- how to implement repository synchronization using Laravel queued jobs;
- how to handle GitHub API pagination;
- how Laravel queue retries, backoff and failed jobs should be handled;
- how to prevent concurrent synchronization of the same GitHub target using database transactions, row-level locking and queue middleware;
- how to track synchronization results and README processing using database logs;
- how to test GitHub API communication without making real HTTP requests;
- how to implement MySQL FULLTEXT search across repository data and README content;
- how to structure Vue/Inertia repository search, filtering and pagination;
- how to improve synchronization status feedback in the UI;
- how to implement a versioned REST API using Laravel Sanctum authentication;
- how to reuse existing validation and synchronization services in API controllers;
- how to structure API Resources and test authenticated API endpoints;
- how to review the implementation against the assignment requirements.

The AI was also instructed to favor maintainable Laravel conventions, avoid unnecessary complexity, and keep the implementation focused on the scope of the assignment.

## Areas with Substantial AI Assistance

AI assistance was particularly useful in:

- designing the synchronization target, repository and synchronization log data models;
- structuring the `GitHubClient` and `SyncTargetService` classes;
- implementing and reviewing the queue-based synchronization flow;
- implementing GitHub API pagination;
- reviewing retry, backoff and failure handling;
- discussing scheduled synchronization and concurrency protection;
- implementing synchronization logging and README processing statistics;
- writing and improving automated tests;
- troubleshooting Node/Vite, Laravel and PHPUnit environment issues;
- implementing and refining repository search using MySQL FULLTEXT;
- refining frontend filtering, pagination and synchronization status handling;
- improving the visual design of the synchronization interface;
- implementing REST API endpoints for synchronization targets and repositories;
- implementing Laravel Sanctum token-based authentication;
- writing REST API Feature tests for authentication, validation, synchronization and repository retrieval.

## Suggestions Changed or Rejected

AI suggestions were not applied automatically. Several suggestions were adjusted or rejected based on the assignment requirements and the intended scope of the project.

Examples include:

- authentication was not required for synchronization targets in the original assignment, but Laravel Sanctum authentication was introduced to protect the optional REST API;
- synchronization target names were made unique regardless of whether the target is a user or organization;
- unnecessary architectural abstractions were avoided where Laravel's existing features were sufficient;
- repository search was implemented on the backend using MySQL FULLTEXT, while language filtering and pagination remained client-side because of the expected dataset size;
- polling was preferred over introducing WebSockets for synchronization status updates;
- synchronization initialization was moved into a shared service instead of duplicating the logic between manual and scheduled synchronization;
- additional reliability mechanisms, such as automatic recovery of synchronization processes stuck in a running state, were considered but not implemented to avoid unnecessary complexity;
- the REST API was implemented as a focused optional feature, exposing the main synchronization operations without duplicating every web interface functionality;
- multitenancy was not implemented to keep the solution focused on the core requirements and selected optional features.

## Validation

AI-generated suggestions were validated through:

- manual review of generated and suggested code;
- automated PHPUnit tests covering repository synchronization, README processing and synchronization initialization;
- mocked GitHub API responses using Laravel HTTP fakes;
- queue dispatch assertions using Laravel's testing utilities;
- manual synchronization against the GitHub API;
- reviewing queue retries, failure handling and synchronization logs;
- frontend testing of synchronization, searching, filtering and pagination;
- Laravel, TypeScript and frontend build checks;
- REST API endpoint testing using Laravel Sanctum authentication;
- API Feature tests covering authentication, target retrieval and creation, duplicate validation, synchronization dispatch and repository retrieval.

Where AI suggestions did not match the requirements or the project's implementation decisions, they were modified or discarded.

The final implementation decisions and responsibility for reviewing and validating the code remained with the developer.
