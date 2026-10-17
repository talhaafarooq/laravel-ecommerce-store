# CLAUDE.md — Laravel Development Standards

## Developer Context

The developer is a senior Laravel/PHP developer.

All code must be written at a senior, production-grade engineering level. Do not provide beginner-level, quick, hacky, or unnecessarily simplified implementations.

Prioritize:
- Clean Architecture
- SOLID principles
- Repository Pattern
- Service Layer / Use Cases
- Dependency Injection
- DRY and KISS
- Maintainability
- Scalability
- Security
- Performance
- Testability

---

## General Rules

1. Before changing code, inspect the existing project structure and related files.
2. Follow the project's existing conventions unless they conflict with these standards.
3. Reuse existing services, repositories, traits, helpers, DTOs, actions, enums, and components where appropriate.
4. Do not create duplicate functionality.
5. Do not introduce unnecessary abstractions or design patterns.
6. Keep code readable, explicit, maintainable, and production-ready.
7. Prefer composition and dependency injection over tightly coupled implementations.
8. Do not make unrelated changes while implementing a feature or fixing a bug.
9. Consider edge cases, validation, authorization, error handling, security, and performance.
10. Preserve backward compatibility unless the requirement explicitly calls for a breaking change.

---

## Architecture

Follow a clean separation of responsibilities.

Preferred flow:

Request / Form Request
        ↓
Controller
        ↓
Service / Use Case
        ↓
Repository Interface
        ↓
Repository Implementation
        ↓
Eloquent Model / Database

### Controllers

Controllers must remain thin.

Controllers should:
- Receive the request.
- Delegate business/application logic.
- Return the appropriate response.
- Handle HTTP-specific concerns only.

Controllers should NOT:
- Contain business logic.
- Contain complex database queries.
- Perform large data transformations.
- Contain complicated conditional workflows.
- Directly implement reusable application logic.

---

## Service Layer

Use Services or Use Cases for application/business logic.

Services should:
- Coordinate business workflows.
- Validate business rules when validation belongs to the application layer.
- Coordinate repositories and other dependencies.
- Keep controllers thin.
- Be independently testable.

Avoid creating services that simply wrap one trivial Eloquent method without providing meaningful abstraction or business value.

---

## Repository Pattern

When Repository Pattern is appropriate, use repository contracts/interfaces.

Preferred structure:

app/
├── Contracts/
│   └── Repositories/
│       └── UserRepositoryInterface.php
├── Repositories/
│   └── UserRepository.php
└── Services/
    └── UserService.php

Repositories should:
- Encapsulate data-access/query logic.
- Keep complex database queries out of controllers and business/application services.
- Expose meaningful methods instead of leaking implementation details.
- Use Eloquent efficiently.

Example:

UserService
    → UserRepositoryInterface
        → UserRepository
            → User model

Use dependency injection:

public function __construct(
    private UserRepositoryInterface $userRepository
) {}

Bind interfaces to implementations through Laravel's container/service provider.

Do not bypass the repository unnecessarily when a repository abstraction is already established for that domain.

Do not force the Repository Pattern for every trivial query if doing so only adds unnecessary complexity.

---

## SOLID Principles

Follow SOLID where applicable:

- Single Responsibility Principle
- Open/Closed Principle
- Liskov Substitution Principle
- Interface Segregation Principle
- Dependency Inversion Principle

Avoid:
- God classes
- God methods
- Massive controllers
- Massive services
- Tight coupling
- Hidden dependencies
- Duplicate business rules

---

## Laravel Standards

Use Laravel's native features and conventions appropriately.

Prefer:
- Form Requests for HTTP validation.
- Policies/Gates for authorization.
- API Resources for API response transformation where appropriate.
- Eloquent relationships.
- Route model binding.
- Jobs/Queues for long-running or asynchronous work.
- Events/Listeners for meaningful decoupling.
- Notifications/Mailables for notifications.
- Enums for fixed domain states where appropriate.
- DTOs/Value Objects when they provide meaningful structure.
- Transactions for multi-step database operations that must be atomic.
- Laravel's dependency injection/container.
- Config and environment variables correctly.

Do not:
- Put business logic in Blade templates.
- Put complex business logic in migrations.
- Use raw SQL when Eloquent/query builder is sufficient.
- Use raw SQL unnecessarily.
- Hard-code credentials, secrets, URLs, or environment-specific values.
- Access `env()` directly throughout application code; use configuration files.
- Disable security mechanisms just to make an implementation work.

---

## Validation

Use Form Request classes for controller-level request validation.

Example:

StoreUserRequest
UpdateUserRequest

Keep validation rules organized and reusable when appropriate.

Business validation that is not HTTP-specific should live in the appropriate service/domain/application layer.

---

## Database & Eloquent

- Use relationships instead of manually joining tables when Eloquent relationships are appropriate.
- Avoid N+1 queries.
- Use eager loading when needed.
- Select only required columns when performance matters.
- Use pagination for large datasets.
- Use chunking/lazy collections for large data processing.
- Use database transactions for atomic operations.
- Add appropriate indexes for frequently queried columns.
- Never load an unnecessarily large dataset into memory.
- Consider query performance before implementing complex operations.

---

## API Development

For APIs:
- Follow consistent RESTful conventions.
- Use Form Requests for validation.
- Use API Resources where appropriate.
- Return appropriate HTTP status codes.
- Keep response structures consistent.
- Handle errors consistently.
- Never expose sensitive information.
- Validate and authorize every protected operation.

---

## Security

Always consider:
- Authentication
- Authorization
- CSRF where applicable
- Mass assignment
- SQL injection
- XSS
- File upload security
- Sensitive data exposure
- Rate limiting
- Input validation
- Access control
- Secure credential handling

Never:
- Commit secrets.
- Log passwords, tokens, API keys, or sensitive credentials.
- Trust user input without validation.
- Bypass authorization checks.

---

## Error Handling

- Handle expected failures explicitly.
- Use appropriate exceptions.
- Do not silently swallow exceptions.
- Do not expose internal exception details to end users in production.
- Log useful diagnostic information without logging secrets.
- Return user-friendly API/application errors.

Avoid excessive try/catch blocks where Laravel's exception handling already provides the correct behavior.

---

## Jobs & Queues

Use Jobs for:
- Long-running operations.
- External API calls when asynchronous processing is appropriate.
- Heavy file processing.
- Email/notification workflows when appropriate.
- Background synchronization.

Jobs should be:
- Idempotent where practical.
- Retry-safe.
- Explicit about dependencies.
- Properly configured for timeout/retry requirements.

Do not put an entire application's business workflow directly inside a Job. Delegate meaningful business logic to appropriate services/use cases.

---

## File Uploads

For file uploads:
- Validate MIME type, extension, and size.
- Use Laravel's filesystem abstraction.
- Avoid trusting the original filename.
- Generate safe filenames when necessary.
- Store files on the appropriate disk.
- Keep upload/business logic outside controllers when it belongs in a service.
- Never allow arbitrary executable uploads into publicly executable locations.

---

## Code Style

Follow PSR-12 and Laravel conventions.

Use:
- Meaningful names.
- Strict and clear types where appropriate.
- Return types.
- Dependency injection.
- Small focused methods.
- Early returns when they improve readability.

Avoid:
- Cryptic variable names.
- Deeply nested conditionals.
- Giant methods.
- Duplicate code.
- Premature optimization.
- Clever code that reduces readability.

---

## Testing

When adding meaningful business logic:
- Consider unit tests.
- Consider feature/integration tests.
- Test success cases.
- Test validation failures.
- Test authorization failures.
- Test important edge cases.
- Test failure scenarios for external services.

Do not change or remove existing tests simply to make the implementation pass unless the test itself is demonstrably incorrect.

---

## Performance

Always consider:
- Query count.
- N+1 problems.
- Memory usage.
- Unnecessary API calls.
- Caching opportunities.
- Queue/background processing.
- Large dataset handling.

Do not optimize prematurely, but do not introduce obvious performance problems.

---

## Existing Project First

Before implementing anything:

1. Inspect related code.
2. Understand the current architecture.
3. Identify existing patterns.
4. Identify reusable classes.
5. Follow existing naming conventions.
6. Check related migrations/models/routes/tests.
7. Implement the smallest clean change that satisfies the requirement.

Do not restructure the entire project unless explicitly requested.

---

## Change Discipline

When fixing a bug:
- Identify the root cause first.
- Fix the root cause rather than masking the symptom.
- Avoid unrelated refactoring.
- Preserve existing behavior outside the affected area.
- Add/update tests when appropriate.

When implementing a feature:
- Understand existing dependencies.
- Identify affected layers.
- Keep responsibilities separated.
- Consider validation, authorization, persistence, API/UI response, and error handling.

---

## Claude Code Behavior

Before coding, inspect the relevant files instead of assuming their contents.

If multiple architectural approaches are possible:
- Prefer the approach that best fits the existing project.
- Choose the simplest architecture that remains clean and scalable.
- Explain important architectural decisions briefly.

Do not generate unnecessary files, classes, interfaces, repositories, services, DTOs, or abstractions merely to satisfy a pattern.

The goal is not to use patterns everywhere. The goal is to produce clean, maintainable, scalable, production-grade Laravel software.

---

## Final Quality Check

Before considering a task complete, verify:

- Does the code follow the existing architecture?
- Is the controller thin?
- Is business logic in the appropriate layer?
- Is repository abstraction used appropriately?
- Are dependencies injected?
- Are validation and authorization handled?
- Are database queries efficient?
- Are edge cases handled?
- Are security concerns addressed?
- Is the code maintainable and readable?
- Did the change avoid unnecessary modifications?
- Are relevant tests updated or added?

Always aim for senior-level production-quality Laravel code.

## Investigation Before Implementation

Before writing or modifying code, inspect the relevant existing implementation.

Never assume:

- file names
- class names
- method names
- database structure
- relationships
- existing architecture

If the task references an existing feature, first locate and inspect that feature.

Do not create duplicate functionality if an existing implementation can be reused.