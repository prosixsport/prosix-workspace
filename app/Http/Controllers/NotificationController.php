<?php

namespace App\Http\Controllers;

use App\Models\OrderNotification;

class NotificationController extends Controller
{
   public function index()
{
    return response()->json(
        \App\Models\OrderNotification::with('order:id,name')
            ->latest()
            ->limit(30)
            ->get()
    );
}

    public function markRead($id)
    {
        $notification = OrderNotification::where('user_id', auth()->id())
            ->findOrFail($id);

        $notification->update([
            'is_read' => true
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    public function markAllRead()
    {
        OrderNotification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update([
                'is_read' => true
            ]);

        return response()->json([
            'success' => true
        ]);
    }
}
