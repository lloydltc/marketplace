# AI Coding Standards

> **AI AGENT INSTRUCTION:** These rules govern how you write and modify code in this project. They extend `docs/DEVELOPMENT_RULES.md` with AI-specific behaviour requirements.

---

## Before Writing Any Code

1. **Identify the existing pattern.** Search the codebase for how similar things are already done. Match that pattern.
2. **Check the module boundary.** Confirm the new code belongs in the module you're about to edit.
3. **Check the layer.** Confirm you're putting business logic in a service, not a controller.
4. **Check for existing solutions.** The feature you're about to build may already exist somewhere.

---

## Code Quality Standards

### Every method you write must:
- Have a single, clear responsibility
- Have typed parameters and return type
- Handle its own edge cases explicitly (not silently)
- Be testable without a running server or database (for business logic)

### Every class you write must:
- Belong to a clearly named namespace matching the directory structure
- Declare its dependencies in the constructor (dependency injection)
- Not access the database directly unless it is a Repository

### Every database migration must:
- Have a complete, tested `down()` method
- Add indexes for all foreign key columns
- Be named according to the project convention

---

## What You Must Always Include

| When you... | You must also... |
|---|---|
| Add a new route | Add authorisation middleware |
| Add a new controller method | Add a Form Request for validation |
| Add a model attribute containing PII | Add encryption cast |
| Add a service method with business logic | Add a unit test |
| Add an API endpoint | Add an API Resource for the response |
| Add a config value | Add it to `.env.example` with a placeholder |
| Fix a bug | Add a regression test |
| Add a new status value | Update the status lifecycle in `BUSINESS_RULES.md` |

---

## What You Must Never Do

| Action | Why |
|---|---|
| Return raw Eloquent models from API responses | Exposes internal structure, breaks versioning |
| Access `$request->input()` directly in a service | Bypasses validation layer |
| Write business logic in a controller | Untestable, not reusable |
| Write raw SQL in a controller | Bypasses ORM, potential injection |
| Use `env()` directly in application code | Use `config()` instead — cached correctly |
| Store sensitive data in logs | Log files may be exposed |
| Leave `dd()`, `dump()`, `var_dump()` in code | Debug leak |
| Suppress exceptions silently with empty catch blocks | Hides failures |
| Use `@` error suppression operator | Hides failures |
| Cast to float for monetary values | Floating-point precision loss |
| Use `sleep()` in a request cycle | Blocks web workers |

---

## Producing Testable Code

```php
// ❌ Hard to test — creates its own dependencies
class CreateApplication
{
    public function run(array $data): Application
    {
        $user = auth()->user();                     // hidden dependency
        $db = DB::table('applications');            // raw DB access
        $id = $db->insertGetId([...]);
        Mail::to($user->email)->send(new ConfirmationMail()); // side effect
        return Application::find($id);
    }
}

// ✅ Testable — all dependencies injected, side effects via events
class CreateApplication
{
    public function __construct(
        private readonly ApplicationRepository $repository,
    ) {}

    public function run(CreateApplicationDTO $dto): Application
    {
        $application = $this->repository->create($dto);
        ApplicationCreated::dispatch($application);  // listener sends the email
        return $application;
    }
}
```

---

## Handling Uncertainty

If you are unsure about:

- **A business rule** — stop and ask. Do not guess at financial or regulatory logic.
- **An architectural decision** — check `ARCHITECTURE.md` and match existing patterns.
- **A security implication** — err on the side of more restriction, not less. Flag the concern.
- **Whether existing functionality will break** — produce an impact analysis and ask for confirmation before proceeding.

---

## Code Review Readiness

Before presenting code, self-review against:

- [ ] Does this code match the style of surrounding code?
- [ ] Would a developer reading this in 6 months understand it without asking me?
- [ ] Are all edge cases handled?
- [ ] Are there tests for the happy path AND the failure paths?
- [ ] Are there any hardcoded values that should be configuration?
- [ ] Are there any security issues (unvalidated input, exposed data, missing authorisation)?
- [ ] Does this break any existing tests?

---

## Commenting Philosophy

```php
// ❌ Redundant — says what the code already says
// Increment the counter by one
$counter++;

// ❌ Wrong level — explains HOW, not WHY
// Loop through each application and check status
foreach ($applications as $application) { ... }

// ✅ Useful — explains WHY and provides context
// Dual-currency periods require separate calculation tracks.
// See Business Rule BR-042 and S.I. 124 of 1992, Section 18(4).
if ($application->hasDualCurrencyPeriod()) { ... }
```

---

## Iterating on Existing Code

When modifying existing code:

1. **Understand it fully before changing it.** Read the method, its callers, and its tests.
2. **Change the minimum necessary.** A surgical change is safer than a rewrite.
3. **Do not fix style issues in the same commit as logic changes.** Separate concerns in separate commits.
4. **Preserve existing behaviour unless the task is to change it.**
5. **If you find a bug while working on something else** — note it in `session_summary.md` and continue. Fix bugs in separate tasks.
