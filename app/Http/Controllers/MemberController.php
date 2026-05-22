<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\InviteMail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    public function index()
    {
        return response()->json(User::latest()->get());
    }

    public function invite(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role'  => 'required|in:member,admin',
        ]);

        $token = Str::random(32);

        cache()->put("invite_{$token}", [
            'name'  => $request->name,
            'email' => $request->email,
            'role'  => $request->role,
        ], now()->addDays(7));

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make(Str::random(16)),
            'role'      => $request->role,
            'is_active' => false,
        ]);

        $inviteLink = url("/register?invite={$token}&email={$request->email}");

        Mail::to($request->email)->send(new InviteMail(
            $inviteLink,
            $request->name,
            $request->role
        ));

        return response()->json([
            'invite_link' => $inviteLink,
            'user' => $user->fresh()
        ]);
    }

    public function profile(User $user)
    {
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'about' => $user->about,
            'profile_photo' => $user->profile_photo,
            'profile_photo_url' => $user->profile_photo_url,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'about' => 'nullable|string|max:2000',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $user->name = $request->name;
        $user->about = $request->about;

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $user->profile_photo = $request->file('profile_photo')->store('profiles', 'public');
        }

        $user->save();

        return response()->json([
            'success' => true,
            'user' => $user->fresh(),
        ]);
    }

    public function toggle($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'super_admin') {
            return response()->json([
                'message' => 'Super admin status cannot be changed'
            ], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'is_active' => $user->is_active,
            'message' => $user->is_active ? 'User activated' : 'User deactivated',
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'super_admin') {
            return response()->json([
                'message' => 'Super admin cannot be deleted'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Member deleted'
        ]);
    }
}
