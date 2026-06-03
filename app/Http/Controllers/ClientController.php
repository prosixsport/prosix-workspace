<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

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
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $data['created_by'] = $request->user()->id;

        $client = Client::create($data);

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
