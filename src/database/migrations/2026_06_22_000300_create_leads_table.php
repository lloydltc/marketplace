<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * D6: every buyer→seller contact event. Vehicles are lead-gen, so this is the
     * core car-side outcome. Captures logged-in AND guest contacts. The listing
     * owner is either a private seller (seller_user_id) or a vendor (vendor_id).
     */
    public function up(): void
    {
        // Private sellers need a contact number for the reveal surface.
        Schema::table('users', function (Blueprint $table) {
            $table->string('contact_phone', 30)->nullable()->after('email');
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 40); // contact_reveal | call_click | whatsapp_click | message | enquiry_form | rfq | concierge | test_drive_request
            $table->string('subject_type')->nullable();   // polymorphic listing (vehicle/product)
            $table->uuid('subject_id')->nullable();
            $table->uuid('seller_user_id')->nullable();    // private-seller owner
            $table->uuid('vendor_id')->nullable();         // vendor owner
            $table->uuid('buyer_user_id')->nullable();     // null = guest
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email')->nullable();
            $table->text('message')->nullable();
            $table->string('source')->nullable();          // utm / referrer
            $table->string('status', 20)->default('new');  // new | contacted | converted | lost
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->foreign('seller_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
            $table->foreign('buyer_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['seller_user_id', 'created_at']);
            $table->index(['vendor_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('contact_phone');
        });
    }
};
