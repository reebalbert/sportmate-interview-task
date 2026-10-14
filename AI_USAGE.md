# AI Usage

AI tools were used as a development assistant during this assignment.

## Tools

- ChatGPT

## How AI Was Used

AI was used to support different parts of the development process, including:

- discussing architecture and implementation decisions;
- reviewing and improving Laravel and Vue code;
- implementing and debugging GitHub API integration and synchronization processes;
- reviewing queue jobs, retries, error handling and concurrency protection;
- implementing repository search using MySQL FULLTEXT;
- improving the frontend design, filtering and pagination;
- implementing and testing the optional REST API with Laravel Sanctum;
- writing and improving automated tests;
- troubleshooting development environment issues;
- reviewing the implementation against the assignment requirements;
- writing and refining the project documentation.

AI-generated suggestions and code were reviewed and adjusted before being included in the project.

## Important Prompts and Instructions

Examples of questions and instructions given to the AI included:

- How should GitHub users, organizations and their repositories be structured in the database?
- How can GitHub API communication be separated from synchronization logic?
- How should repository synchronization be implemented using Laravel queued jobs?
- How can concurrent synchronization of the same target be prevented?
- How should failed jobs, retries and synchronization logs be handled?
- How can GitHub API responses be mocked in automated tests?
- How can MySQL FULLTEXT search include repository names, descriptions and README content?
- How should Vue/Inertia handle repository search, filtering and pagination?
- How can an optional REST API be implemented and protected using Laravel Sanctum?
- How can the implementation be checked against the assignment requirements?

The AI was also instructed to follow Laravel conventions, avoid unnecessary complexity and keep the implementation focused on the assignment requirements.

## Suggestions Changed or Rejected

AI suggestions were not applied automatically. Some approaches were adjusted based on the project's requirements and scope.

Examples include:

- Using polling instead of WebSockets for synchronization status updates.
- Keeping language filtering and pagination on the frontend, while implementing repository search on the backend.
- Moving shared synchronization logic into a service to avoid duplication.
- Using Laravel Sanctum for the optional REST API instead of introducing a separate authentication solution.
- Leaving multitenancy as a possible future improvement rather than implementing it within the assignment.

## Validation

AI-assisted changes were reviewed and tested using:

- manual code review;
- automated PHPUnit tests and mocked GitHub API responses;
- Laravel queue and job dispatch tests;
- manual synchronization against the GitHub API;
- frontend testing of synchronization, search, filtering and pagination;
- Laravel and frontend build checks;
- REST API feature tests covering authentication, validation and synchronization;
- GitHub Actions for automated test execution.

The final implementation decisions and responsibility for reviewing and validating the code remained with the developer.