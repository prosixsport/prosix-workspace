<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

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

        $emailResponse = Http::timeout(15)->withHeaders([
            'api-key' => env('BREVO_API_KEY'),
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => env('MAIL_FROM_NAME', 'Prosix Sports'),
                'email' => env('MAIL_FROM_ADDRESS', 'prosixsports@gmail.com'),
            ],
            'to' => [
                [
                    'email' => $request->email,
                    'name' => $request->name,
                ]
            ],
            'subject' => 'You are invited to Prosix Sports',
            'htmlContent' => $this->inviteEmailHtml(
                $request->name,
                $request->role,
                $inviteLink
            ),
        ]);

        if (!$emailResponse->successful()) {
            $user->delete();

            return response()->json([
                'message' => 'Invite email could not be sent.',
                'brevo_error' => $emailResponse->json(),
            ], 500);
        }

        return response()->json([
            'message' => 'Invitation sent successfully',
            'invite_link' => $inviteLink,
            'user' => $user->fresh()
        ]);
    }

    private function inviteEmailHtml($name, $role, $inviteLink)
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
        </head>
        <body style="margin:0;background:#f5f5f5;font-family:Arial,sans-serif;">
            <div style="max-width:560px;margin:30px auto;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;">
                <div style="background:#000;color:#fff;padding:26px 30px;">
                    <h1 style="margin:0;font-size:24px;">Prosix Sports</h1>
                    <p style="margin:8px 0 0;color:#d1d5db;">Workspace invitation</p>
                </div>

                <div style="padding:30px;">
                    <h2 style="margin:0 0 12px;color:#111;">You are invited!</h2>

                    <p style="color:#374151;font-size:15px;line-height:1.6;">
                        Hello <strong>' . e($name) . '</strong>,
                    </p>

                    <p style="color:#374151;font-size:15px;line-height:1.6;">
                        You have been invited to join <strong>Prosix Sports</strong> as a <strong>' . e($role) . '</strong>.
                    </p>

                    <p style="margin:26px 0;">
                        <a href="' . e($inviteLink) . '"
                           style="background:#000;color:#fff;padding:13px 22px;border-radius:10px;text-decoration:none;font-weight:bold;display:inline-block;">
                            Accept Invitation
                        </a>
                    </p>

                    <p style="color:#6b7280;font-size:13px;line-height:1.5;">
                        If the button does not work, copy this link:
                        <br>
                        <span style="word-break:break-all;">' . e($inviteLink) . '</span>
                    </p>

                    <p style="color:#9ca3af;font-size:12px;margin-top:24px;">
                        This invitation link will expire in 7 days.
                    </p>
                </div>
            </div>
        </body>
        </html>';
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
    public function toggleOrderCreatePermission(User $user)
{
    if (auth()->user()?->role !== 'super_admin') {
        return response()->json([
            'message' => 'Only super admin can change this permission.'
        ], 403);
    }

    if ($user->role === 'super_admin') {
        return response()->json([
            'message' => 'Super admin permission cannot be changed.'
        ], 403);
    }

    $user->can_create_orders = !$user->can_create_orders;
    $user->save();

    return response()->json([
        'success' => true,
        'user' => $user->fresh()
    ]);
}
}
