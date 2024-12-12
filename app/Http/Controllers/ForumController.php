<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use App\Models\ForumPost;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ForumController extends Controller
{
    public function index()
    {
        $author = ForumPost::where('user_id', Auth::id())->first();

        return Inertia::render('Member/Forum/Forum', [
            'postCounts' => ForumPost::count(),
            'authorName' => $author?->display_name
        ]);
    }

    public function createPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'display_name' => ['required'],
        ])->setAttributeNames([
            'display_name' => trans('public.display_name'),
        ]);
        $validator->validate();

        if (!$request->filled('subject') && !$request->filled('message') && !$request->hasFile('attachment')) {
            throw ValidationException::withMessages([
                'subject' => trans('public.at_least_one_field_required'),
            ]);
        }

        try {
            $post = ForumPost::create([
                'user_id' => Auth::id(),
                'display_name' => $request->display_name,
                'subject' => $request->subject,
                'message' => $request->message,
            ]);

            if ($request->attachment) {
                $post->addMedia($request->attachment)->toMediaCollection('post_attachment');
            }

            // Redirect with success message
            return redirect()->back()->with('toast', [
                "title" => trans('public.toast_create_post_success'),
                "type" => "success"
            ]);
        } catch (\Exception $e) {
            // Log the exception and show a generic error message
            Log::error('Error creating the post : '.$e->getMessage());

            return redirect()->back()->with('toast', [
                'title' => 'There was an error creating the post.',
                'type' => 'error'
            ]);
        }
    }

    public function getPosts(Request $request)
    {
        $posts = ForumPost::with([
            'user:id,first_name',
            'media'
        ])
            ->latest()
            ->get()
            ->map(function ($post) {
                $post->profile_photo = $post->user->getFirstMediaUrl('profile_photo');
                $post->post_attachment = $post->getFirstMediaUrl('post_attachment');
                return $post;
            });

        return response()->json($posts);
    }

    public function deletePost(Request $request)
    {
        $post = ForumPost::find($request->id);
    
        // Delete associated user interactions before updating the post
        $post->interactions()->delete(); 
    
        // Reset like and dislike counts before deleting the post
        $post->update([
            'total_likes_count' => 0,
            'total_dislikes_count' => 0,
        ]);
    
        if ($post->hasMedia('post_attachment')) {
            $post->clearMediaCollection('post_attachment');
        }
    
        $post->delete();
    
        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_delete_post_success'),
            'type' => 'success',
        ]);
    }
    
    public function getAgents(Request $request)
    {
        $allRolesInDatabase = Role::all()->pluck('name');

        if (!$allRolesInDatabase->contains('agent')) {
            Role::create(['name' => 'agent']);
        }

        $agentWithoutRole = User::where('role', 'agent')
            ->withoutRole('agent')
            ->get();

        foreach ($agentWithoutRole as $agentRole) {
            $agentRole->syncRoles('agent');
        }

        $allPermissionsInDatabase = Permission::all()->pluck('name');

        if (!$allPermissionsInDatabase->contains('post_forum')) {
            Permission::create(['name' => 'post_forum']);
        }

        $agents = User::role('agent')
            ->where('status', 'active')
            ->select('id', 'first_name', 'email', 'id_number')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('id_number', 'like', '%' . $search . '%');
                });
            })
            ->get()
            ->map(function ($user) {
                $user->isSelected = $user->hasPermissionTo('post_forum');

                return $user;
            });

        $selected_agents = $agents->filter(function ($user) {
            return $user->isSelected;
        });

        $non_selected_agents = $agents->reject(function ($user) {
            return $user->isSelected;
        });

        return response()->json([
            'selectedAgents' => $selected_agents->values(),
            'agents' => $non_selected_agents->values(),
        ]);
    }

    public function updatePostPermission(Request $request)
    {
        $user = User::find($request->id);

        $user->hasPermissionTo('post_forum');

        if ($user->hasPermissionTo('post_forum')) {
            $user->revokePermissionTo('post_forum');
        } else {
            $user->givePermissionTo('post_forum');
        }

        return back()->with('toast', [
            'title' => $user->hasPermissionTo('post_forum') ? trans("public.toast_posting_permission_granted") : trans("public.toast_posting_permission_removed"),
            'type' => 'success',
        ]);
    }

    public function updateLikeCounts(Request $request)
    {
        $post = ForumPost::find($request->id);
    
        // Update the likes or dislikes based on the type
        if ($request->type === 'like') {
            $post->total_likes_count += $request->count;
        } elseif ($request->type === 'dislike') {
            $post->total_dislikes_count += $request->count;
        }
    
        // Save the updated post
        $post->save();
    
        return back();
    }
    
}
