<?php

namespace App\Support;

use App\Models\User;

/**
 * Builds the primary navigation from config/navigation.php so the top nav (and
 * any menu) reflects exactly the logged-in role. This is the single source of
 * truth — route middleware enforces the same rules server-side.
 */
class Navigation
{
    /**
     * @return array<int, array{label: string, url: string}>
     */
    public function for(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $items = [];

        // Dashboard / Home for every authenticated non-customer role.
        if ($user->role !== 'customer' && ($route = $this->dashboardRoute($user->role))) {
            $items[] = ['label' => 'Dashboard', 'url' => route($route)];
        }

        // Buyer context — only for shopping roles (customers).
        if ($this->canShop($user)) {
            foreach (config('navigation.buyer_links', []) as $link) {
                $items[] = ['label' => $link['label'], 'url' => route($link['route'])];
            }
        }

        // Seller "Sales" surface — per-role, from the single config source.
        foreach (config("navigation.seller_links.{$user->role}", []) as $link) {
            $items[] = ['label' => $link['label'], 'url' => route($link['route'])];
        }

        return $items;
    }

    public function dashboardRoute(?string $role): ?string
    {
        return config("navigation.dashboards.{$role}");
    }

    /** Guests and shopping roles may shop; staff roles may not. */
    public function canShop(?User $user): bool
    {
        if ($user === null) {
            return true;
        }

        return in_array($user->role, config('navigation.shopping_roles', []), true);
    }
}
