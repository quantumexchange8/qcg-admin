<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;

class RoleAndPermissionMiddleware
{
    public function handle($request, Closure $next, $role, ...$permissions)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if the user is authenticated
        if (!$user) {
            throw UnauthorizedException::notLoggedIn();
        }

        // If the user has the specified role
        if ($user->hasRole($role)) {
            // Check for the permissions only if the role is matched
            if (!empty($permissions)) {
                $hasPermission = false;

                // Check if the user has at least one of the specified permissions
                foreach ($permissions as $permission) {
                    if ($user->can($permission)) {
                        $hasPermission = true;
                        break;
                    }
                }

                // If the user doesn't have any of the specified permissions, throw UnauthorizedException
                if (!$hasPermission) {
                    throw UnauthorizedException::forPermissions($permissions);
                }
            }
        }

        // If the user doesn't have the specified role (including super-admin or any other role), allow access
        return $next($request);
    }
}
