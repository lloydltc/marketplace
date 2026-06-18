<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * R6 remediation: append-only audit trail for privileged actions (user
     * management, role changes, suspensions, password resets, vendor team
     * changes). Never edited or deleted — corrections are new rows. Mirrors the
     * "Auditability" cross-cutting requirement in BUSINESS_MODEL.md §11.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('actor_id')->nullable();          // user who performed the action
            $table->string('actor_role', 40)->nullable();
            $table->string('action', 80);                  // e.g. user.suspend, vendor.member.remove
            $table->string('target_type', 60)->nullable(); // e.g. App\Models\User
            $table->string('target_id')->nullable();
            $table->jsonb('metadata')->nullable();         // before/after, reason, scope
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['target_type', 'target_id']);
            $table->index(['actor_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
