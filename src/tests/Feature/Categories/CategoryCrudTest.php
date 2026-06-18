<?php

namespace Tests\Feature\Categories;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $user->assignRole('admin');
        return $user;
    }

    private function customer(): User
    {
        $user = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $user->assignRole('customer');
        return $user;
    }

    public function test_admin_can_view_categories_index(): void
    {
        $this->actingAs($this->admin())->get(route('admin.categories.index'))->assertStatus(200);
    }

    public function test_customer_cannot_view_categories_admin(): void
    {
        $this->actingAs($this->customer())->get(route('admin.categories.index'))->assertStatus(403);
    }

    public function test_admin_can_create_category(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), ['name' => 'Engine Parts'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Engine Parts', 'slug' => 'engine-parts']);
    }

    public function test_category_slug_is_auto_generated(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), ['name' => 'Tyres & Rims']);

        $this->assertDatabaseHas('categories', ['slug' => 'tyres-rims']);
    }

    public function test_category_slug_is_unique(): void
    {
        Category::create(['name' => 'Tools', 'slug' => 'tools']);

        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), ['name' => 'Tools']);

        $this->assertDatabaseHas('categories', ['slug' => 'tools-1']);
    }

    public function test_admin_can_create_child_category(): void
    {
        $parent = Category::create(['name' => 'Spare Parts', 'slug' => 'spare-parts']);

        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), [
                'name'      => 'Brakes',
                'parent_id' => $parent->id,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Brakes', 'parent_id' => $parent->id]);
    }

    public function test_admin_can_set_commission_override(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), [
                'name'                => 'Vehicles',
                'commission_override' => 7.5,
            ]);

        $this->assertDatabaseHas('categories', ['name' => 'Vehicles', 'commission_override' => 7.5]);
    }

    public function test_admin_can_update_category(): void
    {
        $category = Category::create(['name' => 'Old Name', 'slug' => 'old-name']);

        $this->actingAs($this->admin())
            ->put(route('admin.categories.update', $category), ['name' => 'New Name'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New Name']);
    }

    public function test_admin_can_delete_category_without_children(): void
    {
        $category = Category::create(['name' => 'Empty Category', 'slug' => 'empty-cat']);

        $this->actingAs($this->admin())
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_cannot_delete_category_with_children(): void
    {
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent']);
        Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);

        $this->actingAs($this->admin())
            ->delete(route('admin.categories.destroy', $parent))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $parent->id, 'deleted_at' => null]);
    }

    public function test_name_is_required(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_commission_override_must_be_between_0_and_100(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), ['name' => 'Test', 'commission_override' => 150])
            ->assertSessionHasErrors('commission_override');
    }
}
