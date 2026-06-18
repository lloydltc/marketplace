<?php

namespace Tests\Unit\Categories;

use App\Modules\Categories\Models\Category;
use PHPUnit\Framework\TestCase;

class CategoryModelTest extends TestCase
{
    private function category(
        ?string $id = null,
        ?string $parentId = null,
        ?float $commissionOverride = null
    ): Category {
        $c = new Category();
        $c->id                  = $id ?? 'test-uuid-' . uniqid();
        $c->parent_id           = $parentId;
        $c->commission_override = $commissionOverride;
        return $c;
    }

    public function test_is_root_returns_true_when_no_parent(): void
    {
        $this->assertTrue($this->category(parentId: null)->isRoot());
    }

    public function test_is_root_returns_false_when_has_parent(): void
    {
        $this->assertFalse($this->category(parentId: 'some-parent-id')->isRoot());
    }

    public function test_get_effective_commission_returns_override_when_set(): void
    {
        $category = $this->category(commissionOverride: 7.5);
        $this->assertEquals(7.5, $category->getEffectiveCommission(10.00));
    }

    public function test_get_effective_commission_returns_default_when_no_override(): void
    {
        $category = $this->category(commissionOverride: null);
        $this->assertEquals(10.00, $category->getEffectiveCommission(10.00));
    }

    public function test_get_effective_commission_returns_custom_default_when_no_override(): void
    {
        $category = $this->category(commissionOverride: null);
        $this->assertEquals(5.00, $category->getEffectiveCommission(5.00));
    }

    public function test_zero_commission_override_is_respected(): void
    {
        $category = $this->category(commissionOverride: 0.0);
        // 0 is a valid override, not "no override"
        $this->assertEquals(0.0, $category->getEffectiveCommission(10.00));
    }
}
