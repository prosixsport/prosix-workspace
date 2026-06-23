<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index()
    {
        return Client::latest()->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],

            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'status' => 'nullable|string',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'client',
            'is_active' => true,
        ]);

        $client = Client::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Client created successfully',
            'client' => $client,
        ], 201);
    }

    public function show(Client $client)
    {
        return $client->load('invoices');
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',

            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($client->user_id)
                    ->whereNull('deleted_at'),
            ],

            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        if ($client->user_id) {
            User::where('id', $client->user_id)->update([
                'name' => $data['name'],
                'email' => $data['email'] ?? $client->email,
            ]);
        }

        $client->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Client updated successfully',
            'client' => $client,
        ]);
    }

    public function destroy(Client $client)
    {
        if ($client->user_id) {
            $user = User::find($client->user_id);

            if ($user) {
                $user->delete();
            }
        }

        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client deleted successfully',
        ]);
    }
}
