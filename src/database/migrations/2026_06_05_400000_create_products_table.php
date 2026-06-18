<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->uuid('category_id');
            $table->string('title', 200);
            $table->text('description');
            $table->string('sku', 50)->nullable();
            $table->decimal('price_zwl', 12, 2);
            $table->decimal('price_usd', 12, 2)->nullable();
            $table->integer('quantity')->default(0);
            $table->enum('status', ['pending', 'active', 'inactive', 'rejected'])->default('pending');
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();

            $table->index('vendor_id');
            $table->index(['category_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // tsvector column for full-text search (cannot be added via Blueprint)
        DB::statement('ALTER TABLE products ADD COLUMN search_vector tsvector');
        DB::statement('CREATE INDEX products_search_vector_gin ON products USING GIN (search_vector)');

        // Partial unique index: SKU must be unique per vendor, but only when set
        DB::statement(
            'CREATE UNIQUE INDEX products_vendor_sku_unique ON products (vendor_id, sku) WHERE sku IS NOT NULL'
        );

        // Trigger function: auto-populate search_vector from title + description
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION products_search_vector_update() RETURNS trigger AS $$
            BEGIN
                NEW.search_vector :=
                    setweight(to_tsvector('english', coalesce(NEW.title, '')), 'A') ||
                    setweight(to_tsvector('english', coalesce(NEW.description, '')), 'B');
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER products_search_vector_trigger
            BEFORE INSERT OR UPDATE ON products
            FOR EACH ROW EXECUTE FUNCTION products_search_vector_update();
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS products_search_vector_trigger ON products');
        DB::statement('DROP FUNCTION IF EXISTS products_search_vector_update()');
        Schema::dropIfExists('products');
    }
};
