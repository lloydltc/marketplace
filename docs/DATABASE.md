# Database

> **AI AGENT INSTRUCTION:** Read this file before writing any queries, migrations, or model definitions. Never drop columns, rename tables, or remove constraints without explicit approval. All migrations must be reversible.

---

## Database Engine

| Setting | Value |
|---|---|
| **Engine** | `PostgreSQL 16` (fallback: MySQL 8) |
| **Connection** | `DB_HOST / DB_PORT / DB_DATABASE` via `.env` |
| **ORM** | `Laravel Eloquent` |
| **Migrations** | `Laravel Migrations — database/migrations/` |
| **Seeders** | `database/seeders/` |
| **Naming Convention** | Snake case, plural table names |

---

## Schema Overview

> List all tables with their purpose. Full definitions below.

| Table | Module | Purpose |
|---|---|---|
| `users` | Auth | System users and credentials |
| `roles` | Auth | Permission roles |
| `role_user` | Auth | User–role pivot |
| `permissions` | Auth | Granular permission definitions |
| `permission_role` | Auth | Role–permission pivot |
| `audit_logs` | Core | System-wide audit trail |
| `[table_name]` | `[Module]` | `[Purpose]` |

---

## Table Definitions

### `users`

**Purpose:** System user accounts.

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | `bigserial` | No | — | Primary key |
| `name` | `varchar(255)` | No | — | Full display name |
| `email` | `varchar(255)` | No | — | Unique login email |
| `password` | `varchar(255)` | No | — | Bcrypt hashed |
| `is_active` | `boolean` | No | `true` | Account active flag |
| `last_login_at` | `timestamptz` | Yes | `null` | Last successful login |
| `created_at` | `timestamptz` | No | `now()` | Record creation |
| `updated_at` | `timestamptz` | No | `now()` | Last modification |
| `deleted_at` | `timestamptz` | Yes | `null` | Soft delete timestamp |

**Indexes:**

| Index Name | Columns | Type | Purpose |
|---|---|---|---|
| `users_email_unique` | `email` | Unique | Login lookup |
| `users_deleted_at_idx` | `deleted_at` | B-tree | Soft delete filtering |

**Relationships:**
- Has many → `roles` (via `role_user`)
- Has many → `audit_logs`

---

### `[table_name]`

**Purpose:** `[Description]`

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | `bigserial` | No | — | Primary key |
| `[column]` | `[type]` | `[Yes/No]` | `[default]` | `[description]` |
| `created_at` | `timestamptz` | No | `now()` | — |
| `updated_at` | `timestamptz` | No | `now()` | — |

**Foreign Keys:**

| Column | References | On Delete |
|---|---|---|
| `[column_id]` | `[table(id)]` | `[CASCADE / RESTRICT / SET NULL]` |

**Indexes:**

| Index Name | Columns | Type | Purpose |
|---|---|---|---|
| `[index_name]` | `[columns]` | `[type]` | `[purpose]` |

---

## Entity Relationship Summary

```
users ──< role_user >── roles ──< permission_role >── permissions

[table_a] ──< [table_b] (via foreign key column_id)
         └── [table_c] (one-to-many)
```

---

## Migration Standards

> All migrations must follow these rules.

### Rules

- Every migration must have a working `down()` method
- Never drop a column that may contain data — deprecate first, remove in a later migration after confirmation
- Never rename a column in production without a coordinated deployment
- Always add an index when adding a foreign key column
- Integer IDs use `bigserial` (PostgreSQL) or `bigIncrements()` (Eloquent)
- All timestamps use `timestamptz` (timezone-aware)
- Use `softDeletes()` for any record that may need to be recovered

### Naming Convention

```
YYYY_MM_DD_HHMMSS_[verb]_[table]_[detail].php

Examples:
  2025_03_01_120000_create_applications_table.php
  2025_03_15_090000_add_status_to_applications_table.php
  2025_04_01_140000_create_index_on_applications_submitted_at.php
```

### Migration Template

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('[table_name]', function (Blueprint $table) {
            $table->id();
            // columns
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('[table_name]');
    }
};
```

---

## Query Standards

### Prefer Eloquent; use raw queries only when necessary

```php
// Preferred
User::where('is_active', true)->get();

// Acceptable for complex queries
DB::select('SELECT ...', []);

// Required when Eloquent cannot express it cleanly (e.g. window functions)
DB::statement('...');
```

### Avoid N+1 queries — always eager load relationships

```php
// Wrong
$users = User::all();
foreach ($users as $user) {
    $user->roles; // N+1
}

// Correct
$users = User::with('roles')->get();
```

### Chunking for large datasets

```php
User::chunk(500, function ($users) {
    // process
});
```

---

## Data Integrity Rules

| Rule | Enforcement |
|---|---|
| No orphaned foreign keys | Database-level `FOREIGN KEY` constraints |
| Monetary values stored as integers (cents/lowest unit) | Application-level enforced |
| PII fields encrypted | Application-level via cast or accessor |
| Soft deletes for recoverable records | `SoftDeletes` trait |
| All status fields have valid value constraints | Database `CHECK` constraint or Enum cast |

---

## Backup Strategy

| Item | Detail |
|---|---|
| **Frequency** | `[e.g. Daily at 02:00 UTC]` |
| **Retention** | `[e.g. 30 days rolling]` |
| **Storage** | `[e.g. Off-site S3 / local volume]` |
| **Restore procedure** | See `docs/DEPLOYMENT_GUIDE.md — Disaster Recovery` |
| **Test restore frequency** | `[e.g. Monthly]` |
