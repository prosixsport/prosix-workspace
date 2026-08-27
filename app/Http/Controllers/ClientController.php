<?php

namespace App\Http\Controllers;

use App\Mail\ClientAccountStatusMail;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class ClientController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Get admin-added customers
    |--------------------------------------------------------------------------
    */

    public function index(): JsonResponse
    {
        $clients = Client::query()
            ->with([
                'user:id,name,email,phone,company,address,role,is_active,account_status,registration_source',
            ])
            ->latest()
            ->get();

        return response()->json($clients);
    }

    /*
    |--------------------------------------------------------------------------
    | Get self-signup customer requests
    |--------------------------------------------------------------------------
    */

    public function requests(): JsonResponse
    {
        $clients = Client::query()
            ->with('user')
            ->whereHas('user', function ($query) {
                $query
                    ->where('role', 'client')
                    ->where('registration_source', 'self');
            })
            ->latest()
            ->get()
            ->map(function (Client $client) {
                $client->setAttribute(
                    'account_status',
                    $client->user?->account_status ?? $client->status
                );

                return $client;
            });

        return response()->json($clients);
    }

    /*
    |--------------------------------------------------------------------------
    | Approve self-signup customer
    |--------------------------------------------------------------------------
    */

    public function approve(Request $request, Client $client): JsonResponse
    {
        $user = $client->user;

        if (!$user || $user->role !== 'client') {
            return response()->json([
                'success' => false,
                'message' => 'Customer account not found.',
            ], 404);
        }

        if ($user->registration_source !== 'self') {
            return response()->json([
                'success' => false,
                'message' => 'Only signup requests can be approved here.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $client, $user) {
                $user->update([
                    'is_active' => true,
                    'account_status' => 'active',
                    'approved_at' => now(),
                    'approved_by' => $request->user()->id,
                ]);

                $client->update([
                    'status' => 'active',
                ]);
            });

            Mail::to($user->email)->send(
                new ClientAccountStatusMail($user->fresh(), 'approved')
            );

            return response()->json([
                'success' => true,
                'message' => 'Customer approved and approval email sent.',
                'client' => $client->fresh()->load('user'),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Customer approve nahi ho saka.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reject self-signup customer
    |--------------------------------------------------------------------------
    */

    public function reject(Request $request, Client $client): JsonResponse
    {
        $user = $client->user;

        if (!$user || $user->role !== 'client') {
            return response()->json([
                'success' => false,
                'message' => 'Customer account not found.',
            ], 404);
        }

        if ($user->registration_source !== 'self') {
            return response()->json([
                'success' => false,
                'message' => 'Only signup requests can be rejected here.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $client, $user) {
                $user->tokens()->delete();

                $user->update([
                    'is_active' => false,
                    'account_status' => 'rejected',
                    'approved_at' => null,
                    'approved_by' => $request->user()->id,
                ]);

                $client->update([
                    'status' => 'rejected',
                ]);
            });

            Mail::to($user->email)->send(
                new ClientAccountStatusMail($user->fresh(), 'rejected')
            );

            return response()->json([
                'success' => true,
                'message' => 'Customer rejected and notification email sent.',
                'client' => $client->fresh()->load('user'),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Customer reject nahi ho saka.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Add client without password
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): JsonResponse
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
                'nullable',
                'string',
                'max:50',
            ],

            'company' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        try {
            $result = DB::transaction(function () use ($request, $data) {
                /*
                 * Admin-added client ka password null rahega.
                 * Client ki first login par password save hoga.
                 */
                $user = User::create([
                    'name' => $data['name'],
                    'email' => strtolower(trim($data['email'])),
                    'phone' => $data['phone'] ?? null,
                    'company' => $data['company'] ?? null,
                    'address' => $data['address'] ?? null,
                    'password' => null,
                    'role' => 'client',
                    'is_active' => $data['status'] === 'active',
                    'account_status' => $data['status'],
                    'registration_source' => 'admin',
                    'approved_at' => $data['status'] === 'active'
                        ? now()
                        : null,
                    'approved_by' => $data['status'] === 'active'
                        ? $request->user()->id
                        : null,
                    'created_by' => $request->user()->id,
                    'can_create_orders' => false,
                ]);

                $client = Client::create([
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'email' => strtolower(trim($data['email'])),
                    'phone' => $data['phone'] ?? null,
                    'company' => $data['company'] ?? null,
                    'address' => $data['address'] ?? null,
                    'status' => $data['status'],
                    'created_by' => $request->user()->id,
                ]);

                return [
                    'user' => $user,
                    'client' => $client,
                ];
            });

            /*
             * Local testing ke liye welcome email direct send hogi.
             */
            Mail::to($result['user']->email)->send(
                new ClientAccountStatusMail(
                    $result['user'],
                    'added'
                )
            );

            return response()->json([
                'success' => true,
                'message' => 'Client added successfully. Welcome email has been sent.',
                'client' => $result['client']->load('user'),
            ], 201);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Client create nahi ho saka.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show client
    |--------------------------------------------------------------------------
    */

    public function show(Client $client): JsonResponse
    {
        return response()->json(
            $client->load([
                'user',
                'invoices',
            ])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update client
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Client $client
    ): JsonResponse {
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

                Rule::unique('users', 'email')
                    ->ignore($client->user_id),

                Rule::unique('clients', 'email')
                    ->ignore($client->id),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'company' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        try {
            $client = DB::transaction(function () use (
                $request,
                $client,
                $data
            ) {
                $email = strtolower(trim($data['email']));

                if ($client->user_id) {
                    $user = User::find($client->user_id);

                    if ($user) {
                        $userData = [
                            'name' => $data['name'],
                            'email' => $email,
                            'phone' => $data['phone'] ?? null,
                            'company' => $data['company'] ?? null,
                            'address' => $data['address'] ?? null,
                            'is_active' => $data['status'] === 'active',
                            'account_status' => $data['status'],
                        ];

                        /*
                         * Inactive se active hone par approval information save.
                         */
                        if (
                            $data['status'] === 'active'
                            && $user->account_status !== 'active'
                        ) {
                            $userData['approved_at'] = now();
                            $userData['approved_by'] = $request->user()->id;
                        }

                        /*
                         * Active se inactive hone par current sessions remove.
                         */
                        if ($data['status'] === 'inactive') {
                            $userData['approved_at'] = null;
                            $user->tokens()->delete();
                        }

                        $user->update($userData);
                    }
                }

                $client->update([
                    'name' => $data['name'],
                    'email' => $email,
                    'phone' => $data['phone'] ?? null,
                    'company' => $data['company'] ?? null,
                    'address' => $data['address'] ?? null,
                    'status' => $data['status'],
                ]);

                return $client;
            });

            return response()->json([
                'success' => true,
                'message' => 'Client updated successfully.',
                'client' => $client->load('user'),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Client update nahi ho saka.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete client
    |--------------------------------------------------------------------------
    */

    public function destroy(Client $client): JsonResponse
    {
        try {
            DB::transaction(function () use ($client) {
                if ($client->user_id) {
                    $user = User::find($client->user_id);

                    if ($user) {
                        $user->tokens()->delete();
                    }
                }

                /*
                 * Client pehle delete karein taake foreign-key issue na aaye.
                 */
                $userId = $client->user_id;

                $client->delete();

                if ($userId) {
                    User::whereKey($userId)->delete();
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Client deleted successfully.',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Client delete nahi ho saka.',
            ], 500);
        }
    }
}
