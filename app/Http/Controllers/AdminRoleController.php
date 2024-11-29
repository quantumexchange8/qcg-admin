<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;

class AdminRoleController extends Controller
{
    public function index()
    {
        $totalAdminRoles = User::whereNotIn('role', ['member', 'agent', 'super-admin'])->count();

        $permissionsList = Permission::query()
            ->where('name', '!=', 'post_forum')
            ->get();
    
        return Inertia::render('AdminRole/AdminRole', [
            'permissionsList' => $permissionsList,
            'totalAdminRoles' => $totalAdminRoles,
        ]);
    }

    public function getAdminRole()
    {
        $admins = User::query()
            ->whereNotIn('role', ['member', 'agent', 'super-admin'])
            ->latest()
            ->get();
        
        // Check roles and assign them if they don't exist
        foreach ($admins as $user) {
            $user->profile_photo = $user->getFirstMediaUrl('profile_photo');
        
            // // Check if the role exists
            // $existing_role = Role::where('name', $user->role)->first();
        
            // // If the role doesn't exist, create a new role
            // if (!$existing_role) {
            //     $existing_role = Role::create(['name' => $user->role, 'guard_name' => 'web']);
            // }
        
            // // Assign the role to the user
            // $user->assignRole($existing_role);
            if (!$user->roles()->exists()) {
                $user->syncRoles('admin');
            }
            
            // Retrieve the permissions for the user
            $user->permissions = $user->getAllPermissions(); // Fetch all permissions for the user
        }
    
        // Return the modified user list in a JSON response
        return response()->json([
            'admins' => $admins
        ]);
    }
    
    public function firstStep(Request $request)
    {
        $rules = [
            'first_name' => 'required|string|max:255|unique:' . User::class,
            'email' => 'required|string|email|max:255|unique:' . User::class,
            'role' => 'nullable|string',
        ];

        $attributes = [
            'first_name' => trans('public.name'),
            'email' => trans('public.email'),
            'role' => trans('public.role'),
        ];

        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($attributes);

        
        $validator->validate();
    }

    public function addNewAdmin(Request $request)
    {
        // Generate a random password
        $minLength = 8;
        $maxLength = 12;
        $length = rand($minLength, $maxLength);
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
        $password = '';
    
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
    
        $role = $request->role ? Str::lower($request->role) : 'admin'; // Default to 'admin' if not provided

        $admin = User::create([
            'first_name' => $request->first_name,
            'email' => $request->email,
            'password' => Hash::make($password),
            'role' => Str::slug($role),
        ]);
    
        $admin->assignRole('admin');
       
        // Handle the profile photo upload using addMedia
        if ($request->profile_photo) {
            $admin->addMedia($request->profile_photo)->toMediaCollection('profile_photo');
        }

        // Assign permissions to the user only if provided
        if ($request->has('permissions')) {
            $admin->givePermissionTo($request->permissions);
        }
    
        // Return a success response
        return back()->with('toast', [
            'title' => trans('public.toast_create_admin_role_success'),
            'type' => 'success',
        ]);
    }

    public function updateAdminStatus(Request $request)
    {
        $admin = User::find($request->id);

        $admin->status = $admin->status == 'active' ? 'inactive' : 'active';
        // $admin->status = $admin->status == 1 ? 0 : 1;
        $admin->save();

        return back()->with('toast', [
            'title' => trans($admin->status === 'active' ? 'public.toast_admin_has_activated' : 'public.toast_admin_has_deactivated'),
            'type' => 'success',
        ]);
    }

    public function adminUpdatePermissions(Request $request)
    {
        $admin = User::findOrFail($request->id);

        $admin->permissions()->detach();
        $admin->givePermissionTo($request->permissions);

        // Return a success response
        return back()->with('toast', [
            'title' => trans('public.toast_admin_update_permissions_success'),
            'type' => 'success',
        ]);
    }
    
    public function editAdmin(Request $request)
    {
        $rules = [
            'first_name' => 'required|string|max:255|unique:' . User::class . ',first_name,' . $request->id,
            'email' => 'required|string|email|max:255|unique:' . User::class . ',email,' . $request->id,
            'role' => 'nullable|string',
        ];

        $attributes = [
            'first_name' => trans('public.name'),
            'email' => trans('public.email'),
            'role' => trans('public.role'),
        ];

        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($attributes);
        $validator->validate();

        // Find the admin user
        $admin = User::findOrFail($request->id);

        // Update the admin user's data
        $admin->update([
            'first_name' => $request->first_name,
            'email' => $request->email,
            'role' => $request->role ? Str::lower($request->role) : 'admin',
        ]);

        // Handle the profile photo
        if ($request->hasFile('profile_photo')) {
            $admin->clearMediaCollection('profile_photo');
            $admin->addMedia($request->profile_photo)->toMediaCollection('profile_photo');
        } elseif ($request->profile_photo === '') {
            // Clear the media if the profile_photo is an empty string
            $admin->clearMediaCollection('profile_photo');
        }

        // Return a success response
        return back()->with('toast', [
            'title' => trans('public.toast_admin_update_permissions_success'),
            'type' => 'success',
        ]);
    }

    public function deleteAdmin(Request $request)
    {
        // Find the user by ID
        $admin = User::find($request->id);
    
        if (!$admin) {
            return back()->with('toast', [
                'title' => 'User Not Found',
                'type' => 'error'
            ]);
        }
    
        // Remove roles and permissions
        if ($admin->roles()->exists()) {
            $admin->roles()->detach(); // Detach all roles
        }
    
        if ($admin->permissions()->exists()) {
            $admin->permissions()->detach(); // Detach all permissions
        }
    
        // Delete the user
        $admin->delete();
    
        // Return success response for admin deletion
        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_delete_admin_role_success'),
            'type' => 'success'
        ]);
    }
}
