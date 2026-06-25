<?php

namespace Tests\Feature\Parts;

use App\Modules\Categories\Models\Category;
use App\Modules\Parts\Models\Part;
use Database\Seeders\PartCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PM1: canonical parts catalog — parts, OEM numbers, alternatives, guides, media,
 * and category reuse.
 */
class PartCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_part_auto_slugs_and_carries_catalog_relations(): void
    {
        $category = Category::create(['name' => 'Brakes', 'slug' => 'brakes-' . Str::random(4), 'sort_order' => 0]);

        $part = Part::create([
            'name' => 'Front Brake Pad Set', 'brand' => 'Bosch', 'category_id' => $category->id,
            'primary_oem' => '04465-0K240', 'warranty_months' => 12, 'is_universal' => false,
        ]);

        $this->assertSame('front-brake-pad-set', $part->slug);
        $this->assertTrue($part->isActive());

        $part->oemNumbers()->create(['number' => '04465-0K240', 'type' => 'oem', 'brand' => 'Toyota']);
        $part->guides()->create(['title' => 'How to fit', 'type' => 'video', 'url' => 'https://x']);
        $part->media()->create(['path' => 'parts/x.jpg', 'is_primary' => true]);

        $this->assertSame(1, $part->oemNumbers()->count());
        $this->assertSame('Brakes', $part->category->name);
        $this->assertNotNull($part->fresh()->primaryImage());
    }

    public function test_slug_is_unique_across_parts(): void
    {
        $a = Part::create(['name' => 'Oil Filter']);
        $b = Part::create(['name' => 'Oil Filter']);

        $this->assertSame('oil-filter', $a->slug);
        $this->assertSame('oil-filter-2', $b->slug);
    }

    public function test_alternatives_link_two_parts(): void
    {
        $oem = Part::create(['name' => 'OEM Filter']);
        $aftermarket = Part::create(['name' => 'Aftermarket Filter']);

        $oem->alternatives()->create(['alternative_part_id' => $aftermarket->id, 'relation' => 'substitute']);

        $this->assertSame($aftermarket->id, $oem->alternatives->first()->alternative->id);
    }

    public function test_related_by_shared_oem_number(): void
    {
        $a = Part::create(['name' => 'Pad A']);
        $b = Part::create(['name' => 'Pad B']);
        $c = Part::create(['name' => 'Pad C']);
        $a->oemNumbers()->create(['number' => 'SHARED-1', 'type' => 'oem']);
        $b->oemNumbers()->create(['number' => 'SHARED-1', 'type' => 'aftermarket']);
        $c->oemNumbers()->create(['number' => 'OTHER-9', 'type' => 'oem']);

        $related = $a->relatedByOem()->pluck('id');

        $this->assertTrue($related->contains($b->id));
        $this->assertFalse($related->contains($c->id));
        $this->assertFalse($related->contains($a->id)); // excludes self
    }

    public function test_category_seeder_reuses_categories_table(): void
    {
        $this->seed(PartCategorySeeder::class);

        $this->assertDatabaseHas('categories', ['slug' => 'engine']);
        $this->assertDatabaseHas('categories', ['slug' => 'service-kits']);
    }
}
