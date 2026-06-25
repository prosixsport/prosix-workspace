<?php

namespace App\Http\Controllers;

use App\Models\OrderNotification;
use App\Events\OrderMessageSent;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Http\Request;

class OrderChatController extends Controller
{
    public function index(Order $order)
    {
        $this->checkAccess($order);

        $userId = auth()->id();

        $messages = OrderMessage::with([
                'user:id,name,email,profile_photo',
                'reads.user:id,name,email,profile_photo',
                'replyTo.user:id,name,email,profile_photo',
            ])
            ->where('order_id', $order->id)
            ->oldest()
            ->get()
            ->filter(function ($msg) use ($userId) {
                return !in_array($userId, $msg->deleted_for ?? []);
            })
            ->map(function ($msg) {
                $reads = $msg->reads
                    ->filter(function ($read) use ($msg) {
                        return (int) $read->user_id !== (int) $msg->user_id;
                    })
                    ->map(function ($read) {
                        return [
                            'user_id' => $read->user_id,
                            'name' => $read->user?->name,
                            'email' => $read->user?->email,
                            'profile_photo_url' => $read->user?->profile_photo_url,
                            'read_at' => $read->read_at
                                ? $read->read_at->format('M d, Y h:i A')
                                : null,
                        ];
                    })
                    ->values();

                return [
                    'id' => $msg->id,
                    'order_id' => $msg->order_id,
                    'user_id' => $msg->user_id,
                    'message' => $msg->message,
                    'reply_to_id' => $msg->reply_to_id,
                    'reply_to' => $msg->replyTo ? [
                        'id' => $msg->replyTo->id,
                        'message' => $msg->replyTo->message,
                        'user' => [
                            'id' => $msg->replyTo->user?->id,
                            'name' => $msg->replyTo->user?->name,
                            'email' => $msg->replyTo->user?->email,
                            'profile_photo_url' => $msg->replyTo->user?->profile_photo_url,
                        ],
                    ] : null,
                    'deleted_for' => $msg->deleted_for,
                    'deleted_everyone_at' => $msg->deleted_everyone_at,
                    'edited_at' => $msg->edited_at,
                    'created_at' => $msg->created_at,
                    'updated_at' => $msg->updated_at,

                    'user' => [
                        'id' => $msg->user?->id,
                        'name' => $msg->user?->name,
                        'email' => $msg->user?->email,
                        'profile_photo_url' => $msg->user?->profile_photo_url,
                    ],

                    'is_seen' => $reads->count() > 0,
                    'reads' => $reads,
                ];
            })
            ->values();

        return response()->json($messages);
    }

    public function store(Request $request, Order $order)
    {
        $this->checkAccess($order);

        $request->validate([
            'message' => 'required|string|max:5000',
            'reply_to_id' => 'nullable|exists:order_messages,id',
        ]);

        $sender = auth()->user();

        $message = OrderMessage::create([
            'order_id' => $order->id,
            'user_id'  => $sender->id,
            'message'  => $request->message,
            'reply_to_id' => $request->reply_to_id,
        ]);

        broadcast(new OrderMessageSent($message));

        $this->sendChatNotifications($order, $sender, $request->message);

        return response()->json([
            'success' => true,
            'message' => $message->load(['user', 'replyTo.user']),
        ]);
    }

    public function update(Request $request, Order $order, OrderMessage $message)
    {
        $this->checkAccess($order);

        if ($message->order_id !== $order->id) {
            abort(404);
        }

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
            'message' => $message->load(['user', 'replyTo.user']),
        ]);
    }

    public function deleteForMe(Order $order, OrderMessage $message)
    {
        $this->checkAccess($order);

        if ($message->order_id !== $order->id) {
            abort(404);
        }

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

        if ($message->order_id !== $order->id) {
            abort(404);
        }

        if ($message->user_id !== auth()->id()) {
            abort(403, 'You can delete only your own message.');
        }

        $message->update([
            'message' => null,
            'deleted_everyone_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $message->load(['user', 'replyTo.user']),
        ]);
    }

    public function unreadCount(Order $order)
    {
        $this->checkAccess($order);

        $count = OrderMessage::where('order_id', $order->id)
            ->where('user_id', '!=', auth()->id())
            ->whereDoesntHave('reads', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markRead(Order $order)
    {
        $this->checkAccess($order);

        $messages = OrderMessage::where('order_id', $order->id)
            ->where('user_id', '!=', auth()->id())
            ->get();

        foreach ($messages as $msg) {
            $read = $msg->reads()->firstOrCreate(
                ['user_id' => auth()->id()],
                ['read_at' => now()]
            );

            if (!$read->read_at) {
                $read->update(['read_at' => now()]);
            }
        }

        return response()->json(['success' => true]);
    }

    private function sendChatNotifications(Order $order, User $sender, string $messageText)
    {
        $members = $order->members()
            ->where('users.id', '!=', $sender->id)
            ->get();

        $clients = $order->clients()
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->where('id', '!=', $sender->id);

        $users = $members->merge($clients);

        if ($sender->role !== 'super_admin') {
            $admins = User::whereIn('role', ['super_admin', 'admin'])
                ->where('id', '!=', $sender->id)
                ->get();

            $users = $users->merge($admins);
        }

        $users = $users->unique('id')->values();

        $shortMessage = mb_strlen($messageText) > 80
            ? mb_substr($messageText, 0, 80) . '...'
            : $messageText;

        foreach ($users as $user) {
            OrderNotification::create([
                'user_id'  => $user->id,
                'order_id' => $order->id,
                'title'    => 'New Chat Message',
                'message'  => $sender->name . ': ' . $shortMessage,
                'is_read'  => 0,
            ]);

            FcmService::send(
                $user->fcm_token,
                'New Chat Message',
                $sender->name . ': ' . $shortMessage,
                [
                    'type' => 'chat',
                    'order_id' => (string) $order->id,
                    'order_name' => $order->name ?? ('Order #' . $order->id),
                ]
            );
        }
    }

    private function checkAccess($order)
    {
        $user = auth()->user();

        if ($user && $user->role === 'super_admin') {
            return true;
        }

        if ($user && $user->role === 'client') {
            $allowed = $order->clients()
                ->where('clients.user_id', $user->id)
                ->exists();

            if (!$allowed) {
                abort(403, 'Access denied');
            }

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
}
