# Salma Tech Automotive Marketplace - AI Development Guide

## Overview

This guide establishes absolute standards for all code written for the Salma Tech Automotive Marketplace. Every AI coding agent, developer, and pull request must adhere to these standards without exception.

**Non-negotiable Requirement**: Production-ready code on first delivery.

---

## 1. General Coding Standards

### 1.1 Code Quality Principles

**SOLID Principles (Non-negotiable)**

- **S**ingle Responsibility: Each class has one reason to change
- **O**pen/Closed: Open for extension, closed for modification
- **L**iskov Substitution: Subclasses are substitutable for parent classes
- **I**nterface Segregation: Many specific interfaces over generic ones
- **D**ependency Inversion: Depend on abstractions, not concretions

**DRY (Don't Repeat Yourself)**

- No code duplication across modules
- Extract common logic into shared services
- Use traits for cross-cutting concerns
- Use inheritance carefully (inheritance hierarchy max depth: 3)

**KISS (Keep It Simple, Stupid)**

- Prefer clarity over cleverness
- Avoid nested conditionals (max nesting: 3 levels)
- Avoid complex ternary operators (max length: 80 chars)
- Prefer explicit over implicit

**YAGNI (You Aren't Gonna Need It)**

- No speculative features
- No premature optimization
- No utility code without current use
- Implement only what's in the requirements

### 1.2 Code Organization

```
app/
├── Modules/
│   └── ModuleName/
│       ├── Actions/              # Business logic orchestration
│       ├── Events/               # Domain events
│       ├── Exceptions/           # Module-specific exceptions
│       ├── Jobs/                 # Queue jobs
│       ├── Listeners/            # Event listeners
│       ├── Models/               # Eloquent models
│       ├── Repositories/         # Data access
│       ├── Requests/             # Form requests & validation
│       ├── Resources/            # API response formatting
│       ├── Services/             # Domain services
│       ├── Tests/
│       │   ├── Feature/
│       │   └── Unit/
│       └── Routes/
```

### 1.3 Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| **Class** | PascalCase | `ProductController`, `OrderRepository` |
| **Method/Function** | camelCase | `getProductByVendor()`, `calculateTotal()` |
| **Variable** | camelCase | `$productId`, `$customerEmail` |
| **Constant** | UPPER_SNAKE_CASE | `MAX_UPLOAD_SIZE`, `COMMISSION_RATE` |
| **Database Table** | snake_case_plural | `products`, `order_items` |
| **Database Column** | snake_case | `created_at`, `vendor_id` |
| **Route** | kebab-case | `/api/products`, `/vendor-dashboard` |
| **Interface** | [Name]Interface | `ProductRepositoryInterface` |
| **Trait** | [Feature]Trait | `HasTimestamps` |
| **Event** | [Action]Event | `ProductCreatedEvent` |
| **Job** | [Action]Job | `ProcessImageJob` |

### 1.4 File Naming

| File Type | Convention | Example |
|-----------|-----------|---------|
| **Controller** | [Name]Controller.php | ProductController.php |
| **Model** | [Name].php | Product.php |
| **Repository** | [Name]Repository.php | ProductRepository.php |
| **Service** | [Name]Service.php | ProductService.php |
| **Request** | [Action][Name]Request.php | StoreProductRequest.php |
| **Resource** | [Name]Resource.php | ProductResource.php |
| **Event** | [Action]Event.php | ProductCreatedEvent.php |
| **Job** | [Action]Job.php | ProcessImageJob.php |
| **Test** | [Name]Test.php | ProductControllerTest.php |
| **Migration** | YYYY_MM_DD_HHMMSS_create_table_name.php | 2024_01_15_120000_create_products_table.php |

---

## 2. Laravel-Specific Standards

### 2.1 Model Development

**Eloquent Model Structure**

```php
namespace App\Modules\Products\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    // UUID primary key
    protected $keyType = 'string';
    public $incrementing = false;

    // Table name (defaults to 'products')
    protected $table = 'products';

    // Fillable attributes
    protected $fillable = [
        'vendor_id',
        'category_id',
        'title',
        'description',
        'sku',
        'price_zwl',
        'quantity',
        'status',
    ];

    // Hidden attributes
    protected $hidden = [
        'deleted_at',
        'internal_notes',
    ];

    // Casted attributes
    protected $casts = [
        'price_zwl' => 'decimal:2',
        'quantity' => 'integer',
        'created_at' => 'datetime',
    ];

    // Accessors
    public function getPriceUsdAttribute(): float
    {
        return round($this->price_zwl * $this->getExchangeRate(), 2);
    }

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('display_order');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByVendor($query, string $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    // Methods
    public function isInStock(): bool
    {
        return $this->quantity > 0 && $this->status === 'active';
    }

    public function calculateRating(): float
    {
        return $this->reviews()
            ->where('status', 'approved')
            ->average('rating') ?? 0;
    }

    private function getExchangeRate(): float
    {
        return Cache::remember('exchange_rate:zwl:usd', 3600, function () {
            return ExchangeRateService::getRate('ZWL', 'USD');
        });
    }
}
```

**Rules**:
- Always use UUIDs (not auto-increment integers)
- Always use `$fillable` (never `$guarded`)
- Always use soft deletes for logical deletion
- Always specify `$casts` for proper type conversion
- Always use relationships over direct queries
- Always define scopes for repeated query logic
- Keep methods focused (max 20 lines)
- Use accessor methods, not raw attributes

### 2.2 Controller Standards

```php
namespace App\Modules\Products\Controllers;

use App\Modules\Products\Requests\StoreProductRequest;
use App\Modules\Products\Requests\UpdateProductRequest;
use App\Modules\Products\Resources\ProductResource;
use App\Modules\Products\Services\ProductService;
use Illuminate\Contracts\Pagination\Paginator;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    // List with pagination
    public function index(): Paginator
    {
        return $this->productService->getProducts(
            filters: request()->all(),
            perPage: request()->get('per_page', 20)
        );
    }

    // Show single resource
    public function show(string $id)
    {
        $product = $this->productService->getProductById($id);

        abort_if($product === null, 404);

        return new ProductResource($product);
    }

    // Store new resource
    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->createProduct(
            data: $request->validated(),
            userId: auth()->id()
        );

        return response()->json(new ProductResource($product), 201);
    }

    // Update resource
    public function update(UpdateProductRequest $request, string $id)
    {
        $product = $this->productService->updateProduct(
            id: $id,
            data: $request->validated(),
            userId: auth()->id()
        );

        return new ProductResource($product);
    }

    // Delete resource
    public function destroy(string $id)
    {
        $this->productService->deleteProduct(id: $id);

        return response()->noContent();
    }
}
```

**Rules**:
- Controllers are thin (delegate to services)
- One public method = one action
- Inject dependencies (not static calls)
- Use named arguments for clarity
- Always validate input with Request classes
- Always return appropriate HTTP status codes
- Use abort_if/abort_unless for authorization
- Use resource classes for API responses

### 2.3 Service Layer Standards

```php
namespace App\Modules\Products\Services;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Events\ProductCreatedEvent;
use App\Modules\Products\Repositories\ProductRepository;
use App\Shared\Services\StorageService;

class ProductService
{
    public function __construct(
        private ProductRepository $repository,
        private StorageService $storage,
        private ProductApprovalService $approvalService
    ) {}

    /**
     * Create a new product
     * 
     * @throws InvalidArgumentException
     * @return Product
     */
    public function createProduct(array $data, string $userId): Product
    {
        // Validation (business logic)
        if (!$this->isVendorAuthorized($userId)) {
            throw new InvalidArgumentException('Vendor not authorized to list products');
        }

        // Create product
        $product = $this->repository->create([
            ...$data,
            'vendor_id' => $userId,
            'status' => 'pending',
        ]);

        // Dispatch event
        event(new ProductCreatedEvent($product));

        return $product;
    }

    /**
     * Update existing product
     */
    public function updateProduct(string $id, array $data, string $userId): Product
    {
        $product = $this->repository->find($id);

        abort_if($product === null, 404, 'Product not found');
        $this->authorize($product, $userId);

        // Only certain fields can be updated
        $updatable = ['title', 'description', 'price_zwl', 'quantity'];
        $data = array_intersect_key($data, array_flip($updatable));

        return $this->repository->update($product, $data);
    }

    private function isVendorAuthorized(string $userId): bool
    {
        $vendor = Vendor::where('user_id', $userId)->first();
        return $vendor?->status === 'approved';
    }

    private function authorize(Product $product, string $userId): void
    {
        abort_if(
            $product->vendor_id !== $userId && !auth()->user()->isAdmin(),
            403,
            'Unauthorized'
        );
    }
}
```

**Rules**:
- Services contain business logic
- Services are stateless
- Methods are public (or private helpers)
- Use type hints everywhere
- Include PHPDoc for public methods
- Throw meaningful exceptions
- Dispatch domain events after state changes
- Keep methods focused (max 30 lines)

### 2.4 Repository Pattern

```php
namespace App\Modules\Products\Repositories;

use App\Modules\Products\Models\Product;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    public function find(string $id): ?Product;
    public function findByVendor(string $vendorId): Collection;
    public function findByCategory(string $categoryId): Paginator;
    public function create(array $data): Product;
    public function update(Product $product, array $data): Product;
    public function delete(Product $product): bool;
}

class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(private Product $model) {}

    public function find(string $id): ?Product
    {
        return $this->model
            ->where('id', $id)
            ->where('deleted_at', null)
            ->with('images', 'vendor')
            ->first();
    }

    public function findByVendor(string $vendorId): Collection
    {
        return $this->model
            ->where('vendor_id', $vendorId)
            ->where('deleted_at', null)
            ->with('images')
            ->get();
    }

    public function create(array $data): Product
    {
        return $this->model->create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->refresh();
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }
}
```

**Rules**:
- Always use repositories for data access
- Define interfaces before implementations
- Repositories are stateless
- Use eager loading to prevent N+1 queries
- Return typed results (Model, Collection, Paginator)

### 2.5 Form Requests (Validation)

```php
namespace App\Modules\Products\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isVendor();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:10', 'max:200'],
            'description' => ['required', 'string', 'min:50', 'max:5000'],
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
            'price_zwl' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'image', 'mimes:jpg,png', 'max:5120'],
            'sku' => ['nullable', 'string', 'max:50', 'unique:products,sku,NULL,id,vendor_id,' . auth()->id()],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Product title is required',
            'title.min' => 'Title must be at least 10 characters',
            'images.required' => 'At least one product image is required',
            'images.max' => 'Maximum 10 images allowed',
        ];
    }

    public function validated($key = null, $default = null): array
    {
        return parent::validated($key, $default);
    }
}
```

**Rules**:
- Always use form requests for validation
- Implement authorize() method
- Return boolean from authorize() (not silent fail)
- Define custom messages for clarity
- Use built-in validation rules wherever possible
- Use custom validation rules for domain logic

### 2.6 Database Migrations

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            // Identifiers
            $table->uuid('id')->primary();
            $table->uuid('vendor_id')->indexed();

            // Data
            $table->string('title', 200);
            $table->text('description');
            $table->string('sku', 50)->nullable();
            $table->decimal('price_zwl', 12, 2);
            $table->integer('quantity')->default(0);

            // Status
            $table->enum('status', ['pending', 'active', 'inactive', 'rejected'])->default('pending');

            // Timestamps & Soft Delete
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('vendor_id');
            $table->index(['status', 'created_at']);
            $table->unique(['vendor_id', 'sku']);

            // Foreign Keys
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

**Rules**:
- Use UUIDs as primary keys
- Always include timestamps
- Use soft deletes for logical deletion
- Always add foreign key constraints
- Use appropriate column types (not all strings)
- Index frequently queried columns
- Use meaningful column names
- Down migrations must be exact reversal of up
- Test migrations both up and down

---

## 3. Database Standards

### 3.1 Schema Design

**Table Creation Rules**

- All tables have UUID primary key (`uuid()->primary()`)
- All tables have timestamps (`timestamps()`)
- All tables have soft delete if user-created content (`softDeletes()`)
- Foreign key constraints enforced
- Indexes on frequently queried columns
- Unique constraints where applicable

**Naming Conventions**

- Table names: `snake_case`, plural (`products`, `order_items`)
- Column names: `snake_case` (`vendor_id`, `created_at`)
- Index names: Auto-generated or explicit (`products_vendor_id_index`)
- Foreign key names: Auto-generated (`products_vendor_id_foreign`)

### 3.2 Query Guidelines

**Eager Loading (Prevent N+1)**

```php
// ❌ Bad: N+1 query
$products = Product::all();
foreach ($products as $product) {
    echo $product->vendor->name;
}

// ✅ Good: Eager loading
$products = Product::with('vendor')->get();
foreach ($products as $product) {
    echo $product->vendor->name;
}
```

**Specific Columns Only**

```php
// ❌ Bad: Select all columns
$vendors = Vendor::get();

// ✅ Good: Select only needed columns
$vendors = Vendor::select('id', 'name', 'rating')->get();
```

**Chunking for Large Datasets**

```php
// ❌ Bad: Load entire table into memory
Product::where('status', 'inactive')->delete();

// ✅ Good: Process in chunks
Product::where('status', 'inactive')->chunk(1000, function ($products) {
    foreach ($products as $product) {
        $product->delete();
    }
});
```

### 3.3 Caching Strategy

```php
// Cache query results
$topProducts = Cache::remember('products:top:7days', 3600, function () {
    return Product::select('id', 'title', 'price_zwl')
        ->where('status', 'active')
        ->orderBy('sales', 'desc')
        ->limit(10)
        ->get();
});

// Cache invalidation on model update
class Product extends Model
{
    protected static function booted(): void
    {
        static::updated(function ($product) {
            Cache::forget('products:top:7days');
            Cache::forget("product:{$product->id}");
        });
    }
}
```

---

## 4. API Standards

### 4.1 Endpoint Design

**RESTful Conventions**

```
GET    /api/v1/products           # List
GET    /api/v1/products/{id}      # Show
POST   /api/v1/products           # Create
PUT    /api/v1/products/{id}      # Update
DELETE /api/v1/products/{id}      # Delete
```

**Response Format (Consistent)**

```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "pagination": {
      "page": 1,
      "per_page": 20,
      "total": 100,
      "pages": 5
    }
  },
  "message": null
}
```

**Error Responses**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": {
      "email": ["Email is required"],
      "password": ["Password must be at least 8 characters"]
    }
  }
}
```

### 4.2 Resource Classes (API Serialization)

```php
namespace App\Modules\Products\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'sku' => $this->sku,
            'prices' => [
                'zwl' => number_format($this->price_zwl, 2),
                'usd' => number_format($this->price_usd, 2),
            ],
            'quantity' => $this->quantity,
            'status' => $this->status,
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'vendor' => new VendorSummaryResource($this->whenLoaded('vendor')),
            'rating' => $this->calculateRating(),
            'reviews_count' => $this->reviews_count ?? 0,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
```

### 4.3 Authentication & Authorization

```php
// Use authentication middleware
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
});

// Use authorization policies
Route::put('/products/{product}', [ProductController::class, 'update'])
    ->middleware('can:update,product');

// Gate definition
Gate::define('update-product', function (User $user, Product $product) {
    return $user->id === $product->vendor_id || $user->isAdmin();
});
```

---

## 5. Testing Standards

### 5.1 Test Structure

**Test Coverage Target**: 80%+
**Test Pyramid**: 10% integration, 70% unit, 20% feature

```php
namespace Tests\Modules\Products\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_list_products()
    {
        $products = Product::factory(10)->create();

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'title', 'prices', 'images']
                ]
            ]);
    }

    public function test_can_create_product()
    {
        $vendor = Vendor::factory()->create(['status' => 'approved']);
        $category = Category::factory()->create();

        $response = $this->actingAs($vendor->user)
            ->postJson('/api/v1/products', [
                'title' => 'Test Product',
                'description' => 'Test Description',
                'category_id' => $category->id,
                'price_zwl' => 99.99,
                'quantity' => 10,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Test Product');

        $this->assertDatabaseHas('products', [
            'title' => 'Test Product',
        ]);
    }

    public function test_cannot_create_product_without_approval()
    {
        $vendor = Vendor::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($vendor->user)
            ->postJson('/api/v1/products', [
                'title' => 'Test',
            ]);

        $response->assertForbidden();
    }
}
```

### 5.2 Unit Tests

```php
namespace Tests\Modules\Products\Unit;

use PHPUnit\Framework\TestCase;
use App\Modules\Products\Services\ProductService;

class ProductServiceTest extends TestCase
{
    public function test_calculates_rating_correctly()
    {
        $product = new Product();
        $product->reviews = collect([
            new Review(['rating' => 5]),
            new Review(['rating' => 4]),
            new Review(['rating' => 3]),
        ]);

        $rating = $product->calculateRating();

        $this->assertEquals(4.0, $rating);
    }
}
```

### 5.3 Test Rules

- Every public method has at least one test
- Test both success and failure cases
- Use descriptive test names: `test_[action]_[condition]_[result]()`
- Use `DatabaseTransactions` for database tests (rollback after each test)
- Use factories for test data creation
- Mock external services (Pesepay, storage)
- Test edge cases (empty arrays, null values, boundary values)
- Include assertions for all side effects

---

## 6. Security Standards

### 6.1 Input Validation & Sanitization

```php
// ✅ Always validate input
$request->validate([
    'email' => ['required', 'email'],
    'password' => ['required', 'string', 'min:10'],
    'phone' => ['nullable', 'regex:/^\+263\d{9}$/'],
]);

// ✅ Always escape output
{{ $product->title }}  {# Blade escaping #}

// ❌ Never trust user input
$product->description = $request->input('description');  // Dangerous

// ✅ Use model mass assignment protection
protected $fillable = ['title', 'description'];
```

### 6.2 Authentication & Authorization

```php
// ✅ Use authentication middleware
Route::middleware('auth:sanctum')->group(function () {
    // Protected routes
});

// ✅ Use authorization policies
if ($user->cannot('update', $product)) {
    abort(403);
}

// ❌ Never hardcode permissions
if ($user->id === $product->vendor_id) { }  // Brittle
```

### 6.3 Sensitive Data Protection

```php
// ✅ Encrypt sensitive fields
protected $encrypted = [
    'ssn',           // Government ID
    'bank_account',  // Bank account
    'pesepay_token', // Payment token
];

// ✅ Hash passwords
Hash::make($password);
Hash::check($password, $hashed);

// ❌ Never log sensitive data
Log::info('User login', ['email' => $email]);  // Dangerous

// ✅ Use .env for secrets
PESEPAY_API_KEY=xxx  // Never in code
```

### 6.4 OWASP Protection

```php
// ✅ CSRF protection (automatic in Laravel)
<form method="POST">
    @csrf
    ...
</form>

// ✅ XSS protection (Blade escaping)
{{ $user->name }}  {# Escaped #}
{!! $html !!}      {# Not escaped, only if trusted #}

// ✅ SQL injection prevention (Eloquent/parameterized)
Product::where('vendor_id', $vendorId)->get();  {# Safe #}
DB::raw("SELECT * FROM products WHERE vendor_id = $vendorId");  {# Dangerous #}

// ✅ Rate limiting
Route::post('/login', [LoginController::class, 'store'])
    ->middleware('throttle:5,1');  {# 5 attempts per 1 minute #}
```

---

## 7. Error Handling & Logging

### 7.1 Exception Handling

```php
// ✅ Define custom exceptions
namespace App\Modules\Products\Exceptions;

class ProductNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('Product not found', 404);
    }
}

// ✅ Handle exceptions gracefully
try {
    $product = $this->repository->find($id);
} catch (ProductNotFoundException $e) {
    abort(404, $e->getMessage());
}

// ✅ Register exception handlers
public function register(): void
{
    $this->reportable(function (ProductNotFoundException $e) {
        // Log or report
    });
}
```

### 7.2 Logging Standards

```php
// ✅ Log important events
Log::info('Product created', [
    'product_id' => $product->id,
    'vendor_id' => $product->vendor_id,
]);

// ✅ Use appropriate log levels
Log::info('User logged in');      // Informational
Log::warning('Low inventory');     // Warning
Log::error('Payment failed');      // Error
Log::critical('Database error');   // Critical

// ❌ Never log sensitive data
Log::info('User login', ['password' => $password]);  // Dangerous

// ✅ Use structured logging
Log::info('Order created', [
    'order_id' => $order->id,
    'total' => $order->total,
    'vendor_id' => $order->vendor_id,
]);
```

---

## 8. Git & Version Control Standards

### 8.1 Branch Strategy

**Feature Branch Workflow**

```
main (production)
├── feature/add-product-filtering
├── feature/implement-reviews
├── bugfix/fix-cart-calculations
└── hotfix/security-patch
```

**Branch Naming**: `feature/description`, `bugfix/description`, `hotfix/description`

### 8.2 Commit Messages

**Format**: `<type>: <description>`

```
feat: add product image optimization
fix: resolve cart quantity calculation error
docs: update API documentation
refactor: simplify product filtering logic
test: add tests for payment processing
chore: update dependencies
```

**Rules**:
- Use imperative mood ("add" not "added")
- First line ≤ 72 characters
- Reference issues: `feat: add filtering (#123)`
- Include description for non-obvious changes

### 8.3 Pull Request Standards

**PR Description Template**

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Feature
- [ ] Bug fix
- [ ] Documentation update

## Testing
- [ ] Unit tests added
- [ ] Feature tests added
- [ ] Tested locally
- [ ] All tests passing

## Checklist
- [ ] Code follows style guidelines
- [ ] No hardcoded values
- [ ] No unnecessary console logs
- [ ] Database migrations included
- [ ] Documentation updated
- [ ] No breaking changes
```

**Review Requirements**:
- All tests must pass
- Code coverage ≥ 80%
- At least 1 approving review
- All conversations resolved
- No conflicts with main branch

---

## 9. Code Review Checklist

Every PR must pass this checklist before merge:

### Functionality
- [ ] Feature works as intended
- [ ] No regressions introduced
- [ ] Error handling appropriate
- [ ] Edge cases handled

### Code Quality
- [ ] SOLID principles followed
- [ ] DRY (no code duplication)
- [ ] KISS (simple and clear)
- [ ] Appropriate design patterns used
- [ ] No dead code
- [ ] No TODO comments without issues

### Testing
- [ ] Unit tests added (critical paths)
- [ ] Feature tests added (user workflows)
- [ ] Test coverage ≥ 80%
- [ ] Edge cases tested
- [ ] Failure scenarios tested

### Security
- [ ] Input validated
- [ ] Output escaped
- [ ] No hardcoded secrets
- [ ] Authentication/authorization checked
- [ ] Sensitive data encrypted
- [ ] SQL injection prevented

### Performance
- [ ] N+1 queries eliminated
- [ ] Caching used appropriately
- [ ] Database indexes considered
- [ ] No unnecessary operations

### Documentation
- [ ] Code commented (complex logic)
- [ ] Public methods documented (PHPDoc)
- [ ] README updated (if applicable)
- [ ] Database migrations documented
- [ ] API endpoints documented

---

## 10. Definition of Done

Code is only "done" when:

✅ All acceptance criteria met
✅ All tests passing (unit, feature, integration)
✅ Code coverage ≥ 80%
✅ Code review approved
✅ No hardcoded values or TODOs
✅ No console.log() or dd()
✅ Security audit passed
✅ Database migrations include down()
✅ Performance optimized (N+1 queries eliminated)
✅ Documentation updated
✅ Ready for production deployment

---

## 11. Production-Ready Code Checklist

Before ANY code reaches production:

- [ ] All features tested
- [ ] All edge cases handled
- [ ] Error messages user-friendly
- [ ] Security audit completed
- [ ] Performance acceptable (< 2s load time)
- [ ] Database backups work
- [ ] Rollback plan documented
- [ ] Monitoring configured
- [ ] Logging configured
- [ ] Caching implemented
- [ ] Rate limiting implemented
- [ ] No debug mode enabled
- [ ] SSL/TLS configured
- [ ] CSRF protection enabled
- [ ] CORS properly configured

---

## 12. Forbidden Practices

❌ **Never commit:**
- API keys or secrets (use .env)
- Passwords in code
- Hardcoded database credentials
- console.log() or dd() statements
- Commented-out code blocks
- TODO/FIXME comments without issues
- node_modules or vendor in git
- Database dumps
- IDE-specific files (.idea/, .vscode/)

❌ **Never do:**
- Write code without tests
- Skip code review
- Merge failing tests
- Commit to main branch directly
- Deploy unreviewed code
- Use raw user input in queries
- Store sensitive data in logs
- Ignore error messages
- Hardcode configuration values
- Mix concerns (business + presentation logic)

---

## 13. AI Agent Instructions

Any AI coding agent must:

1. **Read all documentation first**
   - Read: project_context.md
   - Read: business_rules.md
   - Read: system_architecture.md
   - Read: This guide

2. **Follow this guide exactly**
   - No shortcuts
   - No exceptions
   - No "best practices" that differ from this guide

3. **Always produce production-ready code**
   - Includes tests
   - Includes documentation
   - Includes error handling
   - Includes security measures

4. **Never:**
   - Skip tests
   - Add TODO comments
   - Hardcode values
   - Copy-paste code
   - Ignore error handling

5. **Always:**
   - Use type hints
   - Write PHPDoc
   - Include tests
   - Handle errors
   - Use services layer
   - Follow naming conventions
   - Add database indexes
   - Optimize queries

---

## Appendix: Code Examples

### ✅ Good Code Example

```php
namespace App\Modules\Products\Services;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Events\ProductCreatedEvent;
use App\Modules\Products\Exceptions\InsufficientInventoryException;
use App\Modules\Products\Repositories\ProductRepositoryInterface;

class ProductReservationService
{
    public function __construct(
        private ProductRepositoryInterface $repository
    ) {}

    /**
     * Reserve product inventory for an order
     * 
     * @throws InsufficientInventoryException
     * @return Product
     */
    public function reserve(string $productId, int $quantity): Product
    {
        $product = $this->repository->find($productId);

        if ($product === null) {
            throw new ProductNotFoundException();
        }

        if ($product->quantity < $quantity) {
            throw new InsufficientInventoryException(
                "Only {$product->quantity} items available"
            );
        }

        $product->quantity -= $quantity;
        $this->repository->update($product, [
            'quantity' => $product->quantity,
        ]);

        event(new ProductReservedEvent($product, $quantity));

        return $product;
    }
}
```

### ❌ Bad Code Example

```php
// ❌ Bad: Multiple issues
class ProductService {
    public function reserve($id, $qty) {
        // No type hints
        // No documentation
        // Direct model access
        $product = Product::find($id);
        
        if(!$product) return false;  // No exception
        if($product->quantity < $qty) return false;  // Silent failure
        
        // TODO: validate vendor status
        // TODO: handle concurrent requests
        
        $product->quantity = $product->quantity - $qty;  // Not atomic
        $product->save();
        
        // No event dispatch
        // No logging
        
        return true;
    }
}
```

---

*Document Version: 1.0*  
*Last Updated: 2026*  
*Status: Approved*
