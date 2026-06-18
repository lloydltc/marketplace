<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Categories\Models\Category;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'super_admin'], true);
    }

    public function update(User $user, Category $category): bool
    {
        return in_array($user->role, ['admin', 'super_admin'], true);
    }

    public function delete(User $user, Category $category): bool
    {
        return in_array($user->role, ['admin', 'super_admin'], true);
    }
}
