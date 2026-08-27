<?php

namespace App\Http\Controllers;

use App\Mail\ClientAccountStatusMail;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register
    |--------------------------------------------------------------------------
    | Invite token present:
    |   Admin/member invitation registration.
    |
    | Invite token missing:
    |   Public customer signup request.
    */

    public function register(Request $request): JsonResponse
    {
        if ($request->filled('invite_token')) {
            return $this->registerInvitedUser($request);
        }

        return $this->registerCustomer($request);
    }

    /*
    |--------------------------------------------------------------------------
    | Register invited admin/member
    |--------------------------------------------------------------------------
    */

    private function registerInvitedUser(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'invite_token' => [
                'required',
                'string',
            ],
        ]);

        $inviteData = cache()->get(
            "invite_{$data['invite_token']}"
        );

        if (!$inviteData) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired invite link.',
            ], 403);
        }

        $requestEmail = strtolower(trim($data['email']));
        $inviteEmail = strtolower(trim($inviteData['email'] ?? ''));

        if ($inviteEmail !== $requestEmail) {
            return response()->json([
                'success' => false,
                'message' => 'This invite link is not for this email.',
            ], 403);
        }

        $existingUser = User::where('email', $requestEmail)->first();

        /*
         * Existing client account ko member/admin account mein
         * silently convert nahi hone dena.
         */
        if ($existingUser && $existingUser->role === 'client') {
            return response()->json([
                'success' => false,
                'message' => 'This email is already registered as a client.',
            ], 422);
        }

        $user = User::updateOrCreate(
            [
                'email' => $requestEmail,
            ],
            [
                'name' => $data['name'],
                'password' => Hash::make($data['password']),
                'role' => $inviteData['role'] ?? 'member',
                'is_active' => true,
                'account_status' => 'active',
                'registration_source' => 'invite',
                'approved_at' => now(),
            ]
        );

        cache()->forget(
            "invite_{$data['invite_token']}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully. You can login now.',
            'user' => $user->fresh(),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Public customer signup
    |--------------------------------------------------------------------------
    */

    private function registerCustomer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('clients', 'email'),
            ],

            'phone' => [
                'required',
                'string',
                'max:50',
            ],

            'company' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address' => [
                'required',
                'string',
                'max:2000',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $email = strtolower(trim($data['email']));

        try {
            $result = DB::transaction(function () use ($data, $email) {
                /*
                 * Public signup account pending rahega.
                 * Admin approval se pehle login allow nahi hoga.
                 */
                $user = User::create([
                    'name' => trim($data['name']),
                    'email' => $email,
                    'phone' => $data['phone'],
                    'company' => $data['company'] ?? null,
                    'address' => $data['address'],
                    'password' => Hash::make($data['password']),
                    'role' => 'client',
                    'is_active' => false,
                    'account_status' => 'pending',
                    'registration_source' => 'self',
                    'approved_at' => null,
                    'approved_by' => null,
                    'can_create_orders' => false,
                ]);

                $client = Client::create([
                    'user_id' => $user->id,
                    'name' => trim($data['name']),
                    'email' => $email,
                    'phone' => $data['phone'],
                    'company' => $data['company'] ?? null,
                    'address' => $data['address'],
                    'status' => 'pending',
                    'created_by' => null,
                ]);

                return [
                    'user' => $user,
                    'client' => $client,
                ];
            });

            Mail::to($result['user']->email)->queue(
                new ClientAccountStatusMail(
                    $result['user'],
                    'pending'
                )
            );

            return response()->json([
                'success' => true,
                'message' => 'Your signup request has been submitted. Please allow up to 24 hours for approval. We will notify you by email.',
            ], 201);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Signup request submit nahi ho saki.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    */

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],
        ]);

        $email = strtolower(trim($data['email']));

        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => [
                    'Account not found. Please sign up first.',
                ],
            ]);
        }

        /*
         * Client status login se pehle check hoga.
         */
        if ($user->role === 'client') {
            if ($user->account_status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is pending approval. Please wait for the approval email.',
                ], 403);
            }

            if ($user->account_status === 'rejected') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been rejected or blocked. Please contact Prosix support.',
                ], 403);
            }

            if ($user->account_status === 'inactive') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is inactive.',
                ], 403);
            }
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Admin-added client first login
        |--------------------------------------------------------------------------
        | Admin-added client ka password null hota hai.
        | Pehli login par entered password permanently save hoga.
        */

        if (
            $user->role === 'client'
            && $user->registration_source === 'admin'
            && is_null($user->password)
        ) {
            if (strlen($data['password']) < 8) {
                throw ValidationException::withMessages([
                    'password' => [
                        'First login password must be at least 8 characters.',
                    ],
                ]);
            }

            DB::transaction(function () use ($user, $data) {
                $lockedUser = User::query()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * Concurrent requests mein password sirf ek martaba set hoga.
                 */
                if (is_null($lockedUser->password)) {
                    $lockedUser->update([
                        'password' => Hash::make($data['password']),
                    ]);

                    return;
                }

                /*
                 * Agar kisi aur request ne password set kar diya ho,
                 * entered password us saved password se match hona chahiye.
                 */
                if (
                    !Hash::check(
                        $data['password'],
                        $lockedUser->password
                    )
                ) {
                    throw ValidationException::withMessages([
                        'email' => [
                            'The provided credentials are incorrect.',
                        ],
                    ]);
                }
            });

            $user->refresh();
        } else {
            /*
             * Normal login:
             * saved password match hona lazmi hai.
             */
            if (
                is_null($user->password)
                || !Hash::check($data['password'], $user->password)
            ) {
                throw ValidationException::withMessages([
                    'email' => [
                        'The provided credentials are incorrect.',
                    ],
                ]);
            }
        }

        /*
         * Previous login tokens remove karke fresh token create karna.
         */
        $user->tokens()->delete();

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully.',
            'user' => $user->fresh(),
            'token' => $token,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request): JsonResponse
    {
        if ($request->user()?->currentAccessToken()) {
            $request
                ->user()
                ->currentAccessToken()
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Current authenticated user
    |--------------------------------------------------------------------------
    */

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }
}
