<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Members List
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $users = User::where('role', '!=', 'client')
            ->latest()
            ->get();

        return response()->json($users);
    }

    /*
    |--------------------------------------------------------------------------
    | Invite Member
    |--------------------------------------------------------------------------
    */

    public function invite(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:member,admin',
        ]);

        $token = Str::random(32);

        cache()->put(
            "invite_{$token}",
            [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
            ],
            now()->addDays(7)
        );

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(32)),
            'role' => $validated['role'],
            'is_active' => false,
        ]);

        $inviteLink = url(
            "/register?invite={$token}&email=" . urlencode($validated['email'])
        );

        $emailResponse = Http::timeout(15)
            ->withHeaders([
                'api-key' => env('BREVO_API_KEY'),
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])
            ->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => env('MAIL_FROM_NAME', 'Prosix Sports'),
                    'email' => env(
                        'MAIL_FROM_ADDRESS',
                        'prosixsports@gmail.com'
                    ),
                ],

                'to' => [
                    [
                        'email' => $validated['email'],
                        'name' => $validated['name'],
                    ],
                ],

                'subject' => 'You are invited to Prosix Sports',

                'htmlContent' => $this->inviteEmailHtml(
                    $validated['name'],
                    $validated['role'],
                    $inviteLink
                ),
            ]);

        if (!$emailResponse->successful()) {
            $user->delete();

            cache()->forget("invite_{$token}");

            return response()->json([
                'success' => false,
                'message' => 'Invite email could not be sent.',
                'brevo_error' => $emailResponse->json(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation sent successfully.',
            'invite_link' => $inviteLink,
            'user' => $user->fresh(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Invite Email HTML
    |--------------------------------------------------------------------------
    */

    private function inviteEmailHtml($name, $role, $inviteLink)
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
        </head>

        <body style="
            margin:0;
            background:#f5f5f5;
            font-family:Arial,sans-serif;
        ">

            <div style="
                max-width:560px;
                margin:30px auto;
                background:#ffffff;
                border-radius:16px;
                overflow:hidden;
                border:1px solid #e5e7eb;
            ">

                <div style="
                    background:#000;
                    color:#fff;
                    padding:26px 30px;
                ">
                    <h1 style="
                        margin:0;
                        font-size:24px;
                    ">
                        Prosix Sports
                    </h1>

                    <p style="
                        margin:8px 0 0;
                        color:#d1d5db;
                    ">
                        Workspace invitation
                    </p>
                </div>

                <div style="padding:30px;">

                    <h2 style="
                        margin:0 0 12px;
                        color:#111;
                    ">
                        You are invited!
                    </h2>

                    <p style="
                        color:#374151;
                        font-size:15px;
                        line-height:1.6;
                    ">
                        Hello <strong>' . e($name) . '</strong>,
                    </p>

                    <p style="
                        color:#374151;
                        font-size:15px;
                        line-height:1.6;
                    ">
                        You have been invited to join
                        <strong>Prosix Sports</strong>
                        as a
                        <strong>' . e($role) . '</strong>.
                    </p>

                    <p style="margin:26px 0;">
                        <a
                            href="' . e($inviteLink) . '"
                            style="
                                background:#000;
                                color:#fff;
                                padding:13px 22px;
                                border-radius:10px;
                                text-decoration:none;
                                font-weight:bold;
                                display:inline-block;
                            "
                        >
                            Accept Invitation
                        </a>
                    </p>

                    <p style="
                        color:#6b7280;
                        font-size:13px;
                        line-height:1.5;
                    ">
                        If the button does not work,
                        copy this link:
                        <br>

                        <span style="word-break:break-all;">
                            ' . e($inviteLink) . '
                        </span>
                    </p>

                    <p style="
                        color:#9ca3af;
                        font-size:12px;
                        margin-top:24px;
                    ">
                        This invitation link will expire in 7 days.
                    </p>

                </div>
            </div>

        </body>
        </html>';
    }

    /*
    |--------------------------------------------------------------------------
    | Current Logged-in User Profile
    |--------------------------------------------------------------------------
    */

    public function profile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json([
            'success' => true,

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,

                'avatar' => $user->avatar,
                'job_title' => $user->job_title,

                'about' => $user->about,

                'profile_photo' => $user->profile_photo,
                'profile_photo_url' => $user->profile_photo_url,

                'is_active' => $user->is_active,

                'can_create_orders' => $user->can_create_orders,

                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Current Profile
    |--------------------------------------------------------------------------
    */

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'about' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'profile_photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Basic Profile Fields
        |--------------------------------------------------------------------------
        */

        $user->name = $validated['name'];
        $user->phone = $validated['phone'] ?? null;
        $user->about = $validated['about'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Profile Photo
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('profile_photo')) {

            $photo = $request->file('profile_photo');

            /*
            | Delete previous profile image
            */

            if (
                $user->profile_photo &&
                Storage::disk('public')->exists($user->profile_photo)
            ) {
                Storage::disk('public')->delete(
                    $user->profile_photo
                );
            }

            /*
            | Save new image
            */

            $path = $photo->store(
                'profiles',
                'public'
            );

            $user->profile_photo = $path;
        }

        $user->save();

        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,

                'avatar' => $user->avatar,
                'job_title' => $user->job_title,

                'about' => $user->about,

                'profile_photo' => $user->profile_photo,
                'profile_photo_url' => $user->profile_photo_url,

                'is_active' => $user->is_active,

                'can_create_orders' => $user->can_create_orders,

                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Active / Inactive Member
    |--------------------------------------------------------------------------
    */

    public function toggle($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Super admin status cannot be changed.',
            ], 403);
        }

        $user->is_active = !$user->is_active;

        $user->save();

        return response()->json([
            'success' => true,
            'is_active' => $user->is_active,

            'message' => $user->is_active
                ? 'User activated.'
                : 'User deactivated.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Member
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Super admin cannot be deleted.',
            ], 403);
        }

        /*
        | Delete member profile image too
        */

        if (
            $user->profile_photo &&
            Storage::disk('public')->exists($user->profile_photo)
        ) {
            Storage::disk('public')->delete(
                $user->profile_photo
            );
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Member deleted successfully.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Order Create Permission
    |--------------------------------------------------------------------------
    */

    public function toggleOrderCreatePermission(User $user)
    {
        $authUser = request()->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (
            $authUser->role !== 'super_admin' &&
            !$authUser->can_create_orders
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Only super admin can change this permission.',
            ], 403);
        }

        if ($user->role === 'super_admin') {
            return response()->json([
                'success' => false,
                'message' =>
                    'Super admin permission cannot be changed.',
            ], 403);
        }

        $user->can_create_orders =
            !$user->can_create_orders;

        $user->save();

        return response()->json([
            'success' => true,
            'message' =>
                'Order creation permission updated successfully.',
            'user' => $user->fresh(),
        ]);
    }
}
