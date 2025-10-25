<?php
namespace App\Refactor\Services;

use App\Refactor\Models\Role;

class RoleService
{
    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function update(Role $role, array $data): Role
    {
        $role->update($data);
        return $role;
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }
}
