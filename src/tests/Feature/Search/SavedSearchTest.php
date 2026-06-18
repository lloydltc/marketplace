<?php

namespace Tests\Feature\Search;

use App\Models\SavedSearch;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $user->assignRole('customer');
        return $user;
    }

    public function test_guest_cannot_access_saved_searches(): void
    {
        $this->get(route('saved-searches.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_save_a_search(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('saved-searches.store'), [
                'name'        => 'Cheap brakes',
                'type'        => 'products',
                'q'           => 'brake',
                'max_price'   => '500',
            ])
            ->assertRedirect();

        $search = SavedSearch::where('user_id', $user->id)->first();
        $this->assertNotNull($search);
        $this->assertSame('Cheap brakes', $search->name);
        $this->assertSame('products', $search->type);
        $this->assertSame('brake', $search->query_params['q']);
        $this->assertSame('500', $search->query_params['max_price']);
        $this->assertArrayNotHasKey('name', $search->query_params);
        $this->assertArrayNotHasKey('type', $search->query_params);
    }

    public function test_save_requires_a_name_and_valid_type(): void
    {
        $this->actingAs($this->user())
            ->post(route('saved-searches.store'), ['type' => 'invalid'])
            ->assertSessionHasErrors(['name', 'type']);
    }

    public function test_index_lists_user_saved_searches(): void
    {
        $user = $this->user();
        SavedSearch::create(['user_id' => $user->id, 'name' => 'My Hilux hunt', 'type' => 'vehicles', 'query_params' => ['search' => 'hilux']]);

        $this->actingAs($user)
            ->get(route('saved-searches.index'))
            ->assertOk()
            ->assertSee('My Hilux hunt');
    }

    public function test_user_can_delete_own_saved_search(): void
    {
        $user   = $this->user();
        $search = SavedSearch::create(['user_id' => $user->id, 'name' => 'Temp', 'type' => 'products', 'query_params' => []]);

        $this->actingAs($user)
            ->delete(route('saved-searches.destroy', $search))
            ->assertRedirect();

        $this->assertDatabaseMissing('saved_searches', ['id' => $search->id]);
    }

    public function test_user_cannot_delete_another_users_saved_search(): void
    {
        $owner  = $this->user();
        $other  = $this->user();
        $search = SavedSearch::create(['user_id' => $owner->id, 'name' => 'Theirs', 'type' => 'products', 'query_params' => []]);

        $this->actingAs($other)
            ->delete(route('saved-searches.destroy', $search))
            ->assertForbidden();

        $this->assertDatabaseHas('saved_searches', ['id' => $search->id]);
    }
}
