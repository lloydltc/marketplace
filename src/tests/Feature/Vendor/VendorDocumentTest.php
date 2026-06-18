<?php

namespace Tests\Feature\Vendor;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');
    }

    private function makeVendorWithAdmin(): array
    {
        $vendor = Vendor::create([
            'name'          => 'Doc Vendor',
            'slug'          => 'doc-vendor',
            'contact_email' => 'docs@test.com',
            'status'        => 'pending',
        ]);
        $user = User::factory()->create(['role' => 'vendor_admin', 'email_verified_at' => now()]);
        $user->assignRole('vendor_admin');
        $vendor->users()->attach($user->id, ['vendor_role' => 'admin', 'joined_at' => now()]);
        return [$vendor, $user];
    }

    public function test_vendor_admin_can_upload_document(): void
    {
        [$vendor, $user] = $this->makeVendorWithAdmin();

        $file = UploadedFile::fake()->create('business_reg.pdf', 500, 'application/pdf');

        $this->actingAs($user)
            ->post(route('vendor.documents.store'), [
                'document_type' => 'business_registration',
                'document'      => $file,
            ])
            ->assertRedirect(route('vendor.documents.index'));

        $this->assertDatabaseHas('vendor_documents', [
            'vendor_id'     => $vendor->id,
            'document_type' => 'business_registration',
            'status'        => 'pending',
        ]);
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        [$vendor, $user] = $this->makeVendorWithAdmin();

        $file = UploadedFile::fake()->create('script.php', 100, 'application/x-php');

        $this->actingAs($user)
            ->post(route('vendor.documents.store'), [
                'document_type' => 'business_registration',
                'document'      => $file,
            ])
            ->assertSessionHasErrors('document');
    }

    public function test_admin_can_approve_document(): void
    {
        [$vendor, ] = $this->makeVendorWithAdmin();

        $doc = VendorDocument::create([
            'vendor_id'     => $vendor->id,
            'document_type' => 'tax_id',
            'file_path'     => 'vendor-docs/' . $vendor->id . '/test.pdf',
            'status'        => 'pending',
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.vendors.documents.review', [$vendor, $doc]), ['action' => 'approve'])
            ->assertRedirect();

        $this->assertDatabaseHas('vendor_documents', ['id' => $doc->id, 'status' => 'approved']);
    }

    public function test_admin_can_reject_document_with_reason(): void
    {
        [$vendor, ] = $this->makeVendorWithAdmin();

        $doc = VendorDocument::create([
            'vendor_id'     => $vendor->id,
            'document_type' => 'id_copy',
            'file_path'     => 'vendor-docs/' . $vendor->id . '/id.pdf',
            'status'        => 'pending',
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.vendors.documents.review', [$vendor, $doc]), [
                'action'           => 'reject',
                'rejection_reason' => 'ID copy is blurry and unreadable.',
            ])
            ->assertRedirect();

        $doc->refresh();
        $this->assertEquals('rejected', $doc->status);
        $this->assertEquals('ID copy is blurry and unreadable.', $doc->rejection_reason);
    }
}
