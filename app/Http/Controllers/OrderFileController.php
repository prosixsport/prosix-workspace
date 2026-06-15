<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderFileController extends Controller
{
   public function index(Order $order)
{
    $this->checkAccess($order);

    return response()->json(
        $order->files()->with('user')->latest()->get()
    );
}

public function store(Request $request, Order $order)
{
    $user = auth()->user();
    $this->checkAccess($order);

   if ($user->role !== 'super_admin' && !$user->can_create_orders) {
    abort(403, 'You do not have permission to upload files.');
}

    $request->validate([
        'card_type' => 'required|string',
      'files' => 'required|array',
'files.*' => 'required|file|max:51200',
    ]);

    $saved = [];

foreach ($request->file('files', []) as $file) {
            if (!$file) continue;

        $path = $file->store("order-files/{$order->id}/{$request->card_type}", 'public');

        $saved[] = OrderFile::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'card_type' => $request->card_type,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ])->load('user');
    }

    return response()->json([
        'success' => true,
        'files' => $saved,
    ]);
}

public function storeChatFile(Request $request, Order $order)
{

    $user = auth()->user();
    $this->checkAccess($order);

    $request->validate([
        'files' => 'required',
        'files.*' => 'file|max:51200',
    ]);

    $saved = [];

    foreach ((array) $request->file('files') as $file) {
        if (!$file) continue;

        $path = $file->store("order-files/{$order->id}/chat", 'public');

        $saved[] = OrderFile::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'card_type' => 'chat_files',
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ])->load('user');
    }

    return response()->json([
        'success' => true,
        'files' => $saved,
    ]);
}

public function destroy(OrderFile $file)
{
    $user = auth()->user();

    $order = Order::findOrFail($file->order_id);
    $this->checkAccess($order);

    $isOwner = (int) $file->user_id === (int) $user->id;
    $isSuperAdmin = $user->role === 'super_admin';

    if (!$isOwner && !$isSuperAdmin) {
        abort(403, 'You can delete only your own file.');
    }

    Storage::disk('public')->delete($file->file_path);
    $file->delete();

    return response()->json(['success' => true]);
}

    private function checkAccess(Order $order)
    {
        $user = auth()->user();

        if ($user && $user->role === 'super_admin') return true;

        $allowed = $order->members()
            ->where('users.id', $user?->id)
            ->exists();

        if (!$allowed) abort(403, 'Access denied');

        return true;
    }
}
