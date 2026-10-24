<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Child;
use App\Models\User;

/** A parent may only see and change their own children. */
class ChildPolicy
{
    public const MAX_CHILDREN = 10;

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Parent) && $user->children()->count() < self::MAX_CHILDREN;
    }

    public function update(User $user, Child $child): bool
    {
        return $user->hasRole(Role::Parent) && $child->parent_id === $user->id;
    }

    public function delete(User $user, Child $child): bool
    {
        return $this->update($user, $child);
    }
}
