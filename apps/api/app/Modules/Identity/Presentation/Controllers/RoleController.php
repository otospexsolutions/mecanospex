<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Controllers;

use App\Modules\Identity\Domain\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * List all roles.
     */
    public function index(Request $request): JsonResponse
    {
        $roles = Role::with('permissions')->get();

        $data = $roles->map(fn (Role $role) => [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name'),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
            ],
        ]);
    }

    /**
     * List all permissions.
     */
    public function permissions(Request $request): JsonResponse
    {
        $permissions = Permission::all();

        // Group permissions by module
        $grouped = $permissions->groupBy(function (Permission $permission) {
            $parts = explode('.', $permission->name);

            return $parts[0];
        });

        return response()->json([
            'data' => $grouped->map(fn ($perms) => $perms->pluck('name')),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
            ],
        ]);
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(Request $request, string $userId): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::findOrFail($userId);

        /** @var string $roleName */
        $roleName = $validated['role'];
        $user->assignRole($roleName);

        return response()->json([
            'data' => [
                'message' => "Role '{$roleName}' assigned to user",
                'user_id' => $user->id,
                'roles' => $user->getRoleNames(),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
            ],
        ]);
    }

    /**
     * Remove a role from a user.
     */
    public function removeRole(Request $request, string $userId): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::findOrFail($userId);

        /** @var string $roleName */
        $roleName = $validated['role'];
        $user->removeRole($roleName);

        return response()->json([
            'data' => [
                'message' => "Role '{$roleName}' removed from user",
                'user_id' => $user->id,
                'roles' => $user->getRoleNames(),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
            ],
        ]);
    }

    /**
     * Get user roles and permissions.
     */
    public function userRoles(Request $request, string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
            ],
        ]);
    }
}
