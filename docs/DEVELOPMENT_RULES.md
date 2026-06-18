# Development Rules

> **AI AGENT INSTRUCTION:** These are non-negotiable coding standards. Apply them to every line of code you write or modify. Do not deviate without explicit instruction.

---

## Core Principles

Every decision in this codebase is guided by:

| Principle | Application |
|---|---|
| **Single Responsibility** | One class does one thing. One method does one thing. |
| **Open / Closed** | Extend behaviour — do not modify working code. |
| **Liskov Substitution** | Subtypes must be substitutable for their base types. |
| **Interface Segregation** | Prefer narrow, focused interfaces. |
| **Dependency Inversion** | Depend on abstractions, not concretions. |
| **DRY** | Extract repeated logic immediately. |
| **KISS** | Prefer simple over clever. |
| **YAGNI** | Do not add what is not currently needed. |

---

## PHP and Laravel Standards

### PSR Compliance

- PSR-1: Basic coding standard
- PSR-2 / PSR-12: Coding style (enforced via PHP CS Fixer or Pint)
- PSR-4: Autoloading

### Typing

```php
// Always use typed properties, return types, and parameter types
class UserService
{
    public function __construct(
        private readonly UserRepository $repository,
    ) {}

    public function findActive(int $id): ?User
    {
        return $this->repository->findActiveById($id);
    }
}
```

### Naming Conventions

| Item | Convention | Example |
|---|---|---|
| Classes | `PascalCase` | `PensionCalculator` |
| Methods | `camelCase` | `calculateGratuity()` |
| Variables | `camelCase` | `$pensionAmount` |
| Constants | `UPPER_SNAKE_CASE` | `MAX_SERVICE_YEARS` |
| Database columns | `snake_case` | `submitted_at` |
| Blade views | `kebab-case` | `pension-form.blade.php` |
| Routes | `kebab-case` | `/pension-applications/{id}` |
| Route names | `dot.notation` | `pension.applications.show` |

### Controllers

```php
// Controllers must be thin — delegate everything
class PensionApplicationController extends Controller
{
    public function store(StorePensionApplicationRequest $request): RedirectResponse
    {
        $application = CreatePensionApplication::run(
            CreatePensionApplicationDTO::fromRequest($request)
        );

        return redirect()->route('pension.applications.show', $application)
            ->with('success', 'Application submitted successfully.');
    }
}
```

**Controllers must never:**
- Contain business logic
- Directly query the database
- Contain more than ~30 lines per method
- Handle multiple unrelated concerns

### Actions

```php
// Single-purpose, reusable, testable
class CreatePensionApplication
{
    public static function run(CreatePensionApplicationDTO $dto): PensionApplication
    {
        return DB::transaction(function () use ($dto) {
            $application = PensionApplication::create($dto->toArray());
            ApplicationCreated::dispatch($application);
            return $application;
        });
    }
}
```

### Services

```php
// For multi-step orchestration requiring injected dependencies
class GratuityCalculatorService
{
    public function calculate(PensionApplication $application): Money
    {
        // multi-step calculation logic
    }
}
```

### Repositories

```php
interface UserRepositoryInterface
{
    public function findActiveById(int $id): ?User;
    public function findByEmail(string $email): ?User;
}

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findActiveById(int $id): ?User
    {
        return User::where('is_active', true)->find($id);
    }
}
```

### DTOs

```php
// Typed, immutable data carriers
final class CreatePensionApplicationDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $applicantName,
        public readonly Carbon $dateOfBirth,
        public readonly int $yearsOfService,
    ) {}

    public static function fromRequest(StorePensionApplicationRequest $request): self
    {
        return new self(
            userId: $request->user()->id,
            applicantName: $request->validated('applicant_name'),
            dateOfBirth: Carbon::parse($request->validated('date_of_birth')),
            yearsOfService: (int) $request->validated('years_of_service'),
        );
    }
}
```

### Form Requests

```php
class StorePensionApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PensionApplication::class);
    }

    public function rules(): array
    {
        return [
            'applicant_name' => ['required', 'string', 'max:255'],
            'date_of_birth'  => ['required', 'date', 'before:today'],
            'years_of_service' => ['required', 'integer', 'min:1', 'max:45'],
        ];
    }
}
```

---

## Forbidden Patterns

> AI agents must never produce these patterns.

| Pattern | Reason | Alternative |
|---|---|---|
| Business logic in controllers | Untestable, not reusable | Action or Service class |
| Business logic in models | Violates SRP, hard to test | Service class |
| `DB::table()` in controllers | Bypasses ORM | Repository or Eloquent |
| Hardcoded config values | Environment-specific breakage | Config files and `.env` |
| `sleep()` in synchronous requests | Blocks web workers | Queue jobs |
| Unvalidated user input | Security risk | Form Request |
| `echo` or `var_dump` in production code | Debug leak | Remove before commit |
| `@csrf` token missing on POST forms | CSRF vulnerability | Always include |
| Floating-point arithmetic for money | Precision loss | Integer cents |

---

## Error Handling

```php
// Use typed exceptions for domain errors
class InsufficientServiceYearsException extends DomainException {}

// Handle expected failures explicitly
try {
    $result = $this->calculator->calculate($application);
} catch (InsufficientServiceYearsException $e) {
    return back()->withErrors(['service_years' => $e->getMessage()]);
}

// Let unexpected exceptions bubble to the Handler
```

---

## Comments and Documentation

```php
/**
 * Calculate the gratuity entitlement for a pension application.
 *
 * Based on S.I. 124 of 1992, Section 14(2).
 * Formula: BasicSalary × YearsOfService × AccrualRate
 *
 * @throws InsufficientServiceYearsException if service < MIN_SERVICE_YEARS
 */
public function calculateGratuity(PensionApplication $application): int
```

**Rules:**
- All public methods on Services, Actions, and Repositories must have a docblock
- Reference the business rule ID or legislation if the logic is non-obvious
- Do not comment what the code obviously does — comment WHY
- Delete commented-out code — use version control instead

---

## Git Standards

### Branch Naming

```
feature/[ticket-id]-short-description
bugfix/[ticket-id]-short-description
hotfix/[ticket-id]-short-description
release/v[major.minor.patch]
```

### Commit Message Format

```
[type]([scope]): [short description]

[optional body — what and why, not how]

[optional footer — closes #ticket]
```

**Types:** `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `perf`

**Examples:**
```
feat(pensions): add gratuity calculation for dual-currency periods
fix(auth): correct session expiry for inactive users
refactor(applications): extract status transition to StatusService
```

### Pull Request Rules

- PR must reference a ticket or issue
- PR description must include: what changed, why, and how to test
- All tests must pass before merge
- At least one reviewer approval required
- No self-merges on production branches

---

## Environment Configuration

```php
// Never hardcode — always use config() or env() through config files
// Wrong
$timeout = 30;
$apiUrl = 'https://api.example.com';

// Correct
$timeout = config('services.payments.timeout');
$apiUrl = config('services.payments.url');
```

**`.env` rules:**
- Never commit `.env` — only `.env.example`
- All secrets in `.env`
- All `.env` keys documented in `.env.example` with placeholder values

---

## Logging

```php
// Use structured logging with context
Log::info('Pension application submitted', [
    'application_id' => $application->id,
    'user_id'        => $user->id,
    'amount'         => $amount,
]);

Log::error('Gratuity calculation failed', [
    'application_id' => $application->id,
    'exception'      => $e->getMessage(),
    'trace'          => $e->getTraceAsString(),
]);
```

**Log Levels:**
| Level | Use |
|---|---|
| `debug` | Development only — never in production by default |
| `info` | Normal operations, significant events |
| `warning` | Unexpected but handled situations |
| `error` | Failures that need investigation |
| `critical` | System-level failures requiring immediate action |
