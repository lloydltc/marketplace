# API Standards

> **AI AGENT INSTRUCTION:** All API endpoints must comply with these standards. Never return raw Eloquent models, expose internal error details, or skip authentication on API routes.

---

## API Design Principles

- RESTful resource-oriented design
- Consistent response envelope structure
- Versioned from day one
- Always authenticate and authorise
- Always validate input
- Always return meaningful HTTP status codes
- Never expose internal implementation details in responses or errors

---

## Versioning

```
/api/v1/[resource]
/api/v2/[resource]
```

- Version is in the URL path
- Old versions are maintained with deprecation notices until formally retired
- Deprecation notice in response headers: `Deprecation: true`, `Sunset: [date]`

---

## URL Conventions

| Pattern | Usage | Example |
|---|---|---|
| `/api/v1/[resources]` | Collection | `GET /api/v1/applications` |
| `/api/v1/[resources]/{id}` | Single resource | `GET /api/v1/applications/42` |
| `/api/v1/[resources]/{id}/[sub]` | Sub-resource | `GET /api/v1/applications/42/documents` |
| `/api/v1/[resources]/{id}/[action]` | Resource action | `POST /api/v1/applications/42/approve` |

- Use **nouns**, never verbs in resource paths
- Use **plural** nouns (`applications`, not `application`)
- Use **kebab-case** for multi-word resources (`pension-applications`)
- Never expose database IDs in URLs for sensitive resources — use UUIDs or slugs

---

## HTTP Methods

| Method | Usage | Body | Idempotent |
|---|---|---|---|
| `GET` | Retrieve resource(s) | No | Yes |
| `POST` | Create resource | Yes | No |
| `PUT` | Replace resource (full) | Yes | Yes |
| `PATCH` | Update resource (partial) | Yes | No |
| `DELETE` | Remove resource | No | Yes |

---

## Response Envelope

All API responses use a consistent structure:

### Success (single resource)

```json
{
  "data": {
    "id": 42,
    "type": "application",
    "attributes": {
      "applicant_name": "John Doe",
      "status": "submitted",
      "created_at": "2025-06-01T09:00:00Z"
    }
  },
  "meta": {
    "version": "1.0"
  }
}
```

### Success (collection)

```json
{
  "data": [
    { "id": 1, "type": "application", "attributes": {} },
    { "id": 2, "type": "application", "attributes": {} }
  ],
  "meta": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7
  },
  "links": {
    "first": "/api/v1/applications?page=1",
    "prev": null,
    "next": "/api/v1/applications?page=2",
    "last": "/api/v1/applications?page=7"
  }
}
```

### Error Response

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "applicant_name": ["The applicant name field is required."],
      "date_of_birth":  ["The date of birth must be a valid date."]
    }
  }
}
```

---

## HTTP Status Codes

| Code | Usage |
|---|---|
| `200 OK` | Successful GET, PATCH, DELETE |
| `201 Created` | Successful POST (resource created) |
| `204 No Content` | Successful DELETE with no body |
| `400 Bad Request` | Malformed request |
| `401 Unauthorized` | Not authenticated |
| `403 Forbidden` | Authenticated but not authorised |
| `404 Not Found` | Resource not found |
| `409 Conflict` | State conflict (e.g. duplicate, wrong status) |
| `422 Unprocessable Entity` | Validation failed |
| `429 Too Many Requests` | Rate limit exceeded |
| `500 Internal Server Error` | Unexpected server failure |

---

## Laravel API Resource Implementation

```php
// Always transform responses through API Resources — never return raw models
class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'type'           => 'application',
            'attributes'     => [
                'applicant_name' => $this->applicant_name,
                'status'         => $this->status,
                'created_at'     => $this->created_at->toIso8601String(),
            ],
            'relationships'  => [
                'applicant' => new UserResource($this->whenLoaded('user')),
            ],
        ];
    }
}
```

---

## Authentication

API routes are protected using **Laravel Sanctum** (token-based).

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('applications', ApplicationController::class);
});
```

**Token scopes / abilities:**

| Ability | Description |
|---|---|
| `applications:read` | View applications |
| `applications:write` | Create and update applications |
| `applications:approve` | Approve applications |
| `admin` | Full system access |

---

## Pagination

All collection endpoints must be paginated. Never return unbounded collections.

```php
// Always paginate
$applications = PensionApplication::paginate(15);
return ApplicationResource::collection($applications);
```

---

## Filtering and Sorting

```
GET /api/v1/applications?status=submitted&sort=created_at&direction=desc
GET /api/v1/applications?filter[status]=submitted&filter[year]=2025
```

- Only allow whitelisted filter and sort fields — never expose arbitrary database columns
- Use query scopes or Spatie Query Builder for implementation

---

## API Documentation

- All endpoints must be documented in OpenAPI 3.0 format
- Store spec at `docs/api/openapi.yaml`
- Use Scribe or Swagger UI for rendered documentation
- Keep documentation in sync with implementation — update on every endpoint change

---

## Rate Limiting

```php
// config/api rate limits
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Stricter limit for auth endpoints
RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});
```

---

## Error Handling

```php
// app/Exceptions/Handler.php — consistent API error responses
public function render($request, Throwable $e): Response
{
    if ($request->expectsJson()) {
        if ($e instanceof ValidationException) {
            return response()->json([
                'error' => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => 'The given data was invalid.',
                    'details' => $e->errors(),
                ]
            ], 422);
        }

        if ($e instanceof AuthorizationException) {
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied.']
            ], 403);
        }
    }

    return parent::render($request, $e);
}
```

**Never expose:**
- Stack traces in production API responses
- Database error messages
- Internal class names or file paths
