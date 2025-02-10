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
            if (!empty($permissions)) {
                $hasPermission = false;

                // Check if the user has at least one of the specified permissions
                foreach ($permissions as $permission) {
                    if ($user->can($permission)) {
                        $hasPermission = true;
                        break;
                    }
                }

                // Handle the case where the user lacks access to the dashboard
                if (!$hasPermission && in_array('access_dashboard', $permissions)) {
                    // Redirect based on the user's available permissions
                    return $this->redirectToAvailableRoute();
                }

                if (!$hasPermission) {
                    throw UnauthorizedException::forPermissions($permissions);
                }
            }
        }

        // Proceed if the user role is matched or has sufficient permissions
        return $next($request);
    }

    // Method to determine and redirect to the appropriate route based on available permissions
    private function redirectToAvailableRoute()
    {
        $user = Auth::user();

        $routeMapping = [
            'access_withdrawal_request' => 'pending.withdrawal',
            'access_bonus_request'      => 'pending.bonus',
            'access_incentive_request'  => 'pending.incentive',
            'access_member_listing'     => 'member.listing',
            'access_member_network'     => 'member.network',
            'access_member_forum'       => 'member.forum',
            'access_account_listing'    => 'member.account_listing',
            'access_sales_team'         => 'team',
            'access_rebate_setting'     => 'rebate_setting',
            'access_leaderboard'        => 'leaderboard',
            'access_deposit'            => 'transaction.deposit',
            'access_withdrawal'         => 'transaction.withdrawal',
            'access_transfer'           => 'transaction.transfer',
            'access_bonus'              => 'transaction.bonus',
            'access_rebate_payout'      => 'transaction.rebate',
            'access_incentive_payout'   => 'transaction.incentive',
            'access_account_type'       => 'accountType',
            'access_admin_role'         => 'adminRole',
        ];

        // Find the first permission the user has and redirect to its route
        foreach ($routeMapping as $permission => $route) {
            if ($user->can($permission)) {
                return redirect()->route($route);
            }
        }

        // If no matching permission, throw an UnauthorizedException
        throw UnauthorizedException::forPermissions(array_keys($routeMapping));
    }
}
