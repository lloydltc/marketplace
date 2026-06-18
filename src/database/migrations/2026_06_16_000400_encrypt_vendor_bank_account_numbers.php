<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * P3: encrypt bank account numbers at rest. The ciphertext no longer fits a
     * short varchar and is non-deterministic, so we (1) widen the column to text
     * and (2) replace the plaintext (vendor_id, account_number) unique key with a
     * deterministic HMAC hash column that still enforces per-vendor uniqueness.
     */
    public function up(): void
    {
        Schema::table('vendor_bank_accounts', function (Blueprint $table) {
            $table->dropUnique(['vendor_id', 'account_number']);
        });

        Schema::table('vendor_bank_accounts', function (Blueprint $table) {
            $table->text('account_number')->change();           // holds ciphertext
            $table->string('account_number_hash', 64)->nullable()->after('account_number');
        });

        Schema::table('vendor_bank_accounts', function (Blueprint $table) {
            $table->unique(['vendor_id', 'account_number_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('vendor_bank_accounts', function (Blueprint $table) {
            $table->dropUnique(['vendor_id', 'account_number_hash']);
            $table->dropColumn('account_number_hash');
        });

        Schema::table('vendor_bank_accounts', function (Blueprint $table) {
            $table->string('account_number')->change();
            $table->unique(['vendor_id', 'account_number']);
        });
    }
};
