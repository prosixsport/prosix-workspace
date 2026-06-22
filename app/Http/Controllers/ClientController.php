<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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
        'email' => 'required|email|max:255|unique:users,email',
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

    return response()->json($client, 201);
}

    public function show(Client $client)
    {
        return $client->load('invoices');
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $client->update($data);

        return response()->json($client);
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return response()->json([
            'message' => 'Client deleted successfully'
        ]);
    }
}
