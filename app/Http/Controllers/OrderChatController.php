<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderMessage;
use Illuminate\Http\Request;

class OrderChatController extends Controller
{
    public function index(Order $order)
    {
        $this->checkAccess($order);

        $userId = auth()->id();

        $messages = OrderMessage::with('user')
            ->where('order_id', $order->id)
            ->oldest()
            ->get()
            ->filter(function ($msg) use ($userId) {
                return !in_array($userId, $msg->deleted_for ?? []);
            })
            ->values();

        return response()->json($messages);
    }

    public function store(Request $request, Order $order)
    {
        $this->checkAccess($order);

        $request->validate([
            'message' => 'required|string',
        ]);

        $message = OrderMessage::create([
            'order_id' => $order->id,
            'user_id'  => auth()->id(),
            'message'  => $request->message,
        ]);

        return response()->json([
            'success' => true,
            'message' => $message->load('user'),
        ]);
    }

    public function update(Request $request, Order $order, OrderMessage $message)
    {
        $this->checkAccess($order);

        if ($message->order_id !== $order->id) abort(404);

        if ($message->user_id !== auth()->id()) {
            abort(403, 'You can edit only your own message.');
        }

        if ($message->deleted_everyone_at) {
            abort(422, 'Deleted message cannot be edited.');
        }

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $message->update([
            'message' => $request->message,
            'edited_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $message->load('user'),
        ]);
    }

    public function deleteForMe(Order $order, OrderMessage $message)
    {
        $this->checkAccess($order);

        if ($message->order_id !== $order->id) abort(404);

        $deletedFor = $message->deleted_for ?? [];
        $deletedFor[] = auth()->id();

        $message->update([
            'deleted_for' => array_values(array_unique($deletedFor)),
        ]);

        return response()->json(['success' => true]);
    }

    public function deleteForEveryone(Order $order, OrderMessage $message)
    {
        $this->checkAccess($order);

        if ($message->order_id !== $order->id) abort(404);

        if ($message->user_id !== auth()->id()) {
            abort(403, 'You can delete only your own message.');
        }

        $message->update([
            'message' => null,
            'deleted_everyone_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $message->load('user'),
        ]);
    }

    private function checkAccess($order)
    {
        $user = auth()->user();

        if ($user && $user->role === 'super_admin') {
            return true;
        }

        $allowed = $order->members()
            ->where('users.id', auth()->id())
            ->exists();

        if (!$allowed) {
            abort(403, 'Access denied');
        }

        return true;
    }



public function unreadCount(Order $order)
{
    $count = OrderMessage::where('order_id', $order->id)
        ->where('user_id', '!=', auth()->id())
        ->whereDoesntHave('reads', function ($q) {
            $q->where('user_id', auth()->id());
        })
        ->count();

    return response()->json([
        'count' => $count
    ]);
}

public function markRead(Order $order)
{
    $messages = OrderMessage::where('order_id', $order->id)
        ->where('user_id', '!=', auth()->id())
        ->get();

    foreach ($messages as $msg) {

        $msg->reads()->firstOrCreate([
            'user_id' => auth()->id()
        ], [
            'read_at' => now()
        ]);
    }

    return response()->json([
        'success' => true
    ]);
}
}