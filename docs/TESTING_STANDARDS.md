# Testing Standards

> **AI AGENT INSTRUCTION:** All code you produce must be testable. When adding new features, include test scaffolding. When fixing bugs, add a regression test. Do not remove existing tests.

---

## Testing Philosophy

- **Test behaviour, not implementation** — tests should survive refactoring
- **Tests are documentation** — a test explains what the code is supposed to do
- **Red, Green, Refactor** — write the test first when practical
- **Arrange, Act, Assert** — structure every test this way

---

## Test Categories

| Type | Purpose | Location | Runner |
|---|---|---|---|
| **Unit** | Test a class or method in isolation | `tests/Unit/` | PHPUnit |
| **Feature** | Test a full HTTP request-response cycle | `tests/Feature/` | PHPUnit |
| **Integration** | Test multiple components together | `tests/Integration/` | PHPUnit |
| **Smoke** | Verify key routes are alive post-deployment | `tests/Smoke/` | PHPUnit / Artisan |
| **Regression** | Prevent re-introduction of fixed bugs | Within Unit or Feature | PHPUnit |

---

## Unit Tests

> Test a single class with all dependencies mocked.

```php
class GratuityCalculatorServiceTest extends TestCase
{
    /** @test */
    public function it_calculates_gratuity_for_thirty_years_of_service(): void
    {
        // Arrange
        $service = new GratuityCalculatorService();
        $dto = new CalculationInputDTO(
            basicSalary: 100000,   // cents
            yearsOfService: 30,
        );

        // Act
        $result = $service->calculate($dto);

        // Assert
        $this->assertEquals(75000000, $result->amount); // 100000 × 30 × 0.025
    }

    /** @test */
    public function it_throws_when_service_years_below_minimum(): void
    {
        $this->expectException(InsufficientServiceYearsException::class);

        $service = new GratuityCalculatorService();
        $service->calculate(new CalculationInputDTO(basicSalary: 100000, yearsOfService: 5));
    }
}
```

**Unit test rules:**
- No database connections
- No HTTP calls
- No filesystem access
- Mock all external dependencies with `$this->mock()` or `Mockery`

---

## Feature Tests

> Test a full request through the application stack including the database.

```php
class SubmitPensionApplicationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_submit_application(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/v1/applications', [
                'applicant_name'  => 'John Doe',
                'date_of_birth'   => '1970-05-15',
                'years_of_service' => 25,
            ]);

        // Assert
        $response->assertCreated()
                 ->assertJsonPath('data.attributes.status', 'submitted');

        $this->assertDatabaseHas('pension_applications', [
            'user_id'       => $user->id,
            'status'        => 'submitted',
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_submit_application(): void
    {
        $this->postJson('/api/v1/applications', [])->assertUnauthorized();
    }

    /** @test */
    public function submission_fails_validation_when_name_is_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->postJson('/api/v1/applications', ['years_of_service' => 20])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['applicant_name']);
    }
}
```

---

## Database Factories

```php
// Always use factories — never manually create test data inline
class PensionApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'applicant_name'   => $this->faker->name(),
            'date_of_birth'    => $this->faker->dateTimeBetween('-65 years', '-30 years'),
            'years_of_service' => $this->faker->numberBetween(10, 40),
            'status'           => 'draft',
        ];
    }

    public function submitted(): static
    {
        return $this->state(['status' => 'submitted']);
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }
}
```

---

## Smoke Tests

> Run immediately after every deployment to verify the system is alive.

```php
class SmokePingTest extends TestCase
{
    /** @test */
    public function health_endpoint_returns_ok(): void
    {
        $this->get('/health')->assertOk()->assertJsonPath('status', 'ok');
    }

    /** @test */
    public function login_page_loads(): void
    {
        $this->get('/login')->assertOk();
    }

    /** @test */
    public function api_requires_authentication(): void
    {
        $this->getJson('/api/v1/applications')->assertUnauthorized();
    }
}
```

Run smoke tests with:

```bash
docker compose exec app php artisan test --testsuite=Smoke
```

---

## Test Coverage Requirements

| Module | Minimum Coverage |
|---|---|
| Business rule calculations | 100% |
| Service classes | 90% |
| Controllers | 80% |
| Models (scopes, casts) | 80% |
| API endpoints | 100% of documented endpoints |

Generate coverage report:

```bash
docker compose exec app php artisan test --coverage --min=80
```

---

## Test Configuration

```php
// phpunit.xml
<testsuites>
    <testsuite name="Unit">
        <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory>tests/Feature</directory>
    </testsuite>
    <testsuite name="Smoke">
        <directory>tests/Smoke</directory>
    </testsuite>
</testsuites>

<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="pgsql"/>
    <env name="DB_DATABASE" value="[project]_test"/>
    <env name="CACHE_STORE" value="array"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="MAIL_MAILER" value="array"/>
</php>
```

---

## CI / CD Testing Pipeline

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - name: Install dependencies
        run: composer install --no-dev
      - name: Run tests
        run: php artisan test --parallel
      - name: Security audit
        run: composer audit
```

---

## Regression Testing

> Every bug fix must include a test that would have caught the bug.

```php
/**
 * Regression: Previously gratuity was calculated using salary before deductions.
 * Fixed in commit abc123. Bug report #42.
 *
 * @test
 */
public function gratuity_is_calculated_on_basic_salary_not_gross_salary(): void
{
    // Test that proves the bug is fixed and prevents re-introduction
}
```
