<?php

namespace App\Http\Controllers;
use App\Models\OrderActivity;
use App\Models\OrderNotification;
use App\Models\Order;
use App\Models\OrderRead;
use App\Models\OrderWorkSession;
use App\Models\User;
use App\Mail\NewOrderAssignedMail;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
public function index()
{
    $user = auth()->user();

    $orders = Order::with([
            'members',
            'clients',
            'activeWorkSession.user:id,name,email,role,profile_photo',
            'reads.user:id,name,email,profile_photo',
            'messages' => function ($q) {
                $q->with('user:id,name')
                  ->latest()
                  ->limit(1);
            }
        ])
        ->withCount([
            'messages as unread_chat_count' => function ($q) use ($user) {
                $q->where('user_id', '!=', $user?->id)
                  ->whereDoesntHave('reads', function ($r) use ($user) {
                      $r->where('user_id', $user?->id);
                  });
            }
        ])
        ->withMax('messages as last_message_at', 'created_at')
        ->when($user && $user->role === 'client', function ($q) use ($user) {
            $q->whereHas('clients', function ($c) use ($user) {
                $c->where('clients.user_id', $user->id);
            });
        })
        ->when($user && !in_array($user->role, ['super_admin', 'admin', 'client']), function ($q) use ($user) {
            $q->whereHas('members', function ($m) use ($user) {
                $m->where('users.id', $user->id);
            });
        })
        ->orderByDesc('last_message_at')
        ->latest()
        ->get()
        ->map(function ($order) use ($user) {
            $read = $order->reads->firstWhere('user_id', $user?->id);

            $order->user_has_seen = !empty($read?->read_at);
            $order->read_at = $read?->read_at;

            $lastMessage = $order->messages->first();

            $order->last_message_text = $lastMessage?->message;
            $order->last_message_sender = $lastMessage?->user?->name;
            $order->last_message_time = $lastMessage?->created_at?->diffForHumans();

            $activeWork = $order->activeWorkSession;

            $order->working_by = $activeWork?->user
                ? [
                    'id' => $activeWork->user->id,
                    'name' => $activeWork->user->name,
                    'email' => $activeWork->user->email,
                    'role' => $activeWork->user->role,
                    'profile_photo_url' => $activeWork->user->profile_photo_url,
                    'started_at' => $activeWork->started_at,
                ]
                : null;

            return $order;
        });

    return response()->json($orders);
}

  public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'member_ids' => 'nullable|array',
        'member_ids.*' => 'exists:users,id',
        'client_ids' => 'nullable|array',
        'client_ids.*' => 'exists:clients,id',
        'shipping_address' => 'nullable|string',
    ]);

    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'message' => 'Unauthenticated. Please login again.'
        ], 401);
    }

if ($user->role !== 'super_admin' && !$user->can_create_orders && $user->role !== 'client') {
            return response()->json([
            'message' => 'You do not have permission to create orders.'
        ], 403);
    }

    $order = Order::create([
        'name'             => $request->name,
        'po'               => $request->po,
        'ship_date'        => $request->ship_date,
        'status'           => $request->status ?? 'Pending',
        'status_color'     => $request->status_color ?? '#fdab3d',
        'trk'              => $request->trk,
        'payment'          => $request->payment ?? '0 % Paid',
        'payment_received' => $request->payment_received ?? 0,
        'payment_balance'  => $request->payment_balance ?? 0,
        'notes'            => $request->notes ?? '',
        'shipping_address' => $request->shipping_address,
        'created_by'       => $user->id,
    ]);

    $order->members()->syncWithoutDetaching([
        $user->id => ['role' => 'admin']
    ]);

    foreach ($request->member_ids ?? [] as $memberId) {
        $order->members()->syncWithoutDetaching([
            $memberId => ['role' => 'member']
        ]);
    }

if ($user->role === 'client') {
    $client = \App\Models\Client::where('user_id', $user->id)->first();

    if (!$client) {
        $client = \App\Models\Client::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
        ]);
    }

    $order->clients()->sync([$client->id]);
    Mail::raw(
    "New Client Order Created\n\n" .
    "Client Name: {$user->name}\n" .
    "Client Email: {$user->email}\n\n" .
    "Order Name: {$order->name}\n" .
    "PO: {$order->po}\n" .
    "Ship Date: {$order->ship_date}\n" .
    "Shipping Address: {$order->shipping_address}\n" .
    "Status: {$order->status}\n" .
    "Tracking: {$order->trk}\n" .
    "Payment: {$order->payment}\n" .
    "Notes: {$order->notes}\n",
    function ($message) use ($order) {
        $message->to('prosixsports@gmail.com')
            ->subject('New Client Order: ' . $order->name);
    }
);
} else {
    $order->clients()->sync($request->client_ids ?? []);
}
    $this->logActivity(
    $order->id,
    'created',
    auth()->user()->name . ' created order "' . $order->name . '"'
);

    $this->sendNewOrderNotifications($order, $request->member_ids ?? [], $user->id);

    return response()->json([
        'success' => true,
        'order'   => $order->load(['members', 'clients'])
    ]);
}

    public function show(Order $order)
    {
        $this->checkAccess($order);

        return response()->json(
            $order->load(['members', 'messages.user', 'activeWorkSession.user'])
        );
    }

public function update(Request $request, Order $order)
{
    $this->checkAccess($order);

    $user = auth()->user();
    if ($user && $user->role === 'client') {
    return response()->json([
        'message' => 'Clients can only view orders.'
    ], 403);
}
    $isSuperAdmin = $user && $user->role === 'super_admin';

    $oldStatus = $order->status;
    $oldTracking = $order->trk;
    $oldNotes = $order->notes;
    $oldPayment = $order->payment;
    $oldReceived = $order->payment_received;
    $oldBalance = $order->payment_balance;

    $request->validate([
        'name' => 'nullable|string|max:255',
        'po' => 'nullable|string|max:255',
        'ship_date' => 'nullable|date',
        'status' => 'nullable|string|max:255',
        'status_color' => 'nullable|string|max:255',
        'trk' => 'nullable|string|max:255',
        'payment' => 'nullable|string|max:255',
        'payment_received' => 'nullable|numeric',
        'payment_balance' => 'nullable|numeric',
        'notes' => 'nullable|string',
        'shipping_address' => 'nullable|string',

        'member_ids' => 'nullable|array',
        'member_ids.*' => 'exists:users,id',

        'client_ids' => 'nullable|array',
        'client_ids.*' => 'exists:clients,id',
    ]);

    $oldMemberIds = $order->members()
        ->pluck('users.id')
        ->map(fn ($id) => (int) $id)
        ->toArray();

if ($isSuperAdmin || $user->can_create_orders) {
            $order->update($request->only([
            'name',
            'po',
            'ship_date',
            'status',
            'status_color',
            'trk',
            'payment',
            'payment_received',
            'payment_balance',
            'notes',
            'shipping_address',
        ]));

        if ($request->has('member_ids')) {
            $syncData = [];

            foreach ($request->member_ids ?? [] as $memberId) {
                $syncData[$memberId] = ['role' => 'member'];
            }

            if ($order->created_by) {
                $syncData[$order->created_by] = ['role' => 'admin'];
            }

            if (auth()->id()) {
                $syncData[auth()->id()] = ['role' => 'admin'];
            }

            $order->members()->sync($syncData);

            $newMemberIds = collect(array_keys($syncData))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => !in_array($id, $oldMemberIds))
                ->filter(fn ($id) => $id !== (int) auth()->id())
                ->unique()
                ->values();

            $this->sendNewOrderNotifications($order, $newMemberIds, auth()->id());
        }

        if ($request->has('client_ids')) {
            $order->clients()->sync($request->client_ids ?? []);
        }

    } else {
    $allowedFields = [
    'status',
    'status_color',
    'trk',
];

if ($user->can_create_orders) {
    $allowedFields = array_merge($allowedFields, [
        'notes',
        'payment',
        'payment_received',
        'payment_balance',
        'shipping_address',
        'ship_date',
    ]);
}

$order->update($request->only($allowedFields));

    }

    $order->refresh();

    if ($request->has('notes') && $oldNotes !== $order->notes) {
        $this->sendOrderActivityNotification(
            $order,
            auth()->id(),
            'Notes Updated',
            auth()->user()->name . ' changed notes in order: ' . $order->name
        );
    }

    if ($request->has('status') && $oldStatus !== $order->status) {
        $this->sendOrderActivityNotification(
            $order,
            auth()->id(),
            'Status Updated',
            auth()->user()->name . ' changed status from ' . ($oldStatus ?: 'N/A') . ' to ' . $order->status . ' in order: ' . $order->name
        );
    }

    if ($request->has('trk') && $oldTracking !== $order->trk) {
        $this->sendOrderActivityNotification(
            $order,
            auth()->id(),
            'Tracking Updated',
            auth()->user()->name . ' changed tracking in order: ' . $order->name
        );
    }

    if (
        ($request->has('payment') && $oldPayment !== $order->payment) ||
        ($request->has('payment_received') && (float) $oldReceived !== (float) $order->payment_received) ||
        ($request->has('payment_balance') && (float) $oldBalance !== (float) $order->payment_balance)
    ) {
        $this->sendOrderActivityNotification(
            $order,
            auth()->id(),
            'Payment Updated',
            auth()->user()->name . ' changed payment details in order: ' . $order->name
        );
    }

    return response()->json([
        'success' => true,
        'order' => $order->load(['members', 'clients'])
    ]);
}

    public function destroy(Order $order)
    {
        $this->checkAccess($order);

if (auth()->user()?->role !== 'super_admin' && !auth()->user()?->can_create_orders) {
                return response()->json([
                'message' => 'Only super admin can delete orders.'
            ], 403);
        }

$this->logActivity(
    $order->id,
    'deleted',
    auth()->user()->name . ' moved order "' . $order->name . '" to Recycle Bin'
);
        $order->delete();

        return response()->json(['success' => true]);
    }

    public function addMember(Request $request, Order $order)
    {
        $this->checkAccess($order);

        if (auth()->user()?->role !== 'super_admin') {
            return response()->json([
                'message' => 'Only super admin can add members.'
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $exists = $order->members()
            ->where('user_id', $request->user_id)
            ->exists();

        if (!$exists) {
            $order->members()->attach($request->user_id, [
                'role' => 'member'
            ]);

            $this->sendNewOrderNotifications($order, [$request->user_id], auth()->id());
        }

        return response()->json([
            'success' => true,
            'members' => $order->members()->get()
        ]);
    }

    public function removeMember(Order $order, User $user)
    {
        $this->checkAccess($order);

        if (auth()->user()?->role !== 'super_admin') {
            return response()->json([
                'message' => 'Only super admin can remove members.'
            ], 403);
        }

        $order->members()->detach($user->id);

        return response()->json(['success' => true]);
    }

    public function markRead($orderId)
    {
        $user = auth()->user();
        $order = Order::findOrFail($orderId);

        if (in_array($user->role, ['super_admin', 'admin'])) {
            return response()->json(['success' => true]);
        }

        DB::table('order_reads')->updateOrInsert(
            [
                'order_id' => $order->id,
                'user_id'  => $user->id,
            ],
            [
                'read_at'    => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json(['success' => true]);
    }

    public function readInfo(Order $order)
    {
        $this->checkAccess($order);

        $reads = OrderRead::with('user:id,name,email')
            ->where('order_id', $order->id)
            ->latest('read_at')
            ->get()
            ->map(function ($read) {
                return [
                    'name' => $read->user?->name,
                    'email' => $read->user?->email,
                    'read_at' => $read->read_at?->format('M d, Y h:i A'),
                ];
            });

        return response()->json(['reads' => $reads]);
    }


public function claim(Order $order)
{
    $this->checkAccess($order);

    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.'
        ], 401);
    }

    return DB::transaction(function () use ($order, $user) {
        $activeWork = OrderWorkSession::query()
            ->with('user:id,name,email,role,profile_photo')
            ->where('order_id', $order->id)
            ->whereNull('ended_at')
            ->lockForUpdate()
            ->first();

        if (
            $activeWork &&
            (int) $activeWork->user_id !== (int) $user->id
        ) {
            return response()->json([
                'success' => false,
                'message' => ($activeWork->user?->name ?? 'Another member')
                    . ' is already working on this order.',
                'working_by' => $activeWork->user
                    ? [
                        'id' => $activeWork->user->id,
                        'name' => $activeWork->user->name,
                        'email' => $activeWork->user->email,
                        'role' => $activeWork->user->role,
                        'profile_photo_url' =>
                            $activeWork->user->profile_photo_url,
                        'started_at' => $activeWork->started_at,
                    ]
                    : null,
            ], 409);
        }

        if (!$activeWork) {
            $activeWork = OrderWorkSession::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'started_at' => now(),
                'last_seen_at' => now(),
            ]);
        } else {
            $activeWork->update([
                'last_seen_at' => now(),
            ]);
        }

        $activeWork->load(
            'user:id,name,email,role,profile_photo'
        );

        return response()->json([
            'success' => true,
            'message' => 'You are now working on this order.',
            'working_by' => [
                'id' => $activeWork->user->id,
                'name' => $activeWork->user->name,
                'email' => $activeWork->user->email,
                'role' => $activeWork->user->role,
                'profile_photo_url' =>
                    $activeWork->user->profile_photo_url,
                'started_at' => $activeWork->started_at,
            ],
        ]);
    });
}

public function release(Order $order)
{
    $this->checkAccess($order);

    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.'
        ], 401);
    }

    $activeWork = OrderWorkSession::query()
        ->where('order_id', $order->id)
        ->whereNull('ended_at')
        ->first();

    if (!$activeWork) {
        return response()->json([
            'success' => true,
            'message' => 'This order is already free.'
        ]);
    }

    $canRelease =
        (int) $activeWork->user_id === (int) $user->id ||
        in_array($user->role, ['super_admin', 'admin'], true);

    if (!$canRelease) {
        return response()->json([
            'success' => false,
            'message' => 'You cannot stop another member’s work.'
        ], 403);
    }

    $activeWork->update([
        'ended_at' => now(),
        'last_seen_at' => now(),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Working status stopped.',
        'working_by' => null,
    ]);
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
        ->where('users.id', $user?->id)
        ->exists();

    if (!$allowed) {
        abort(403, 'Access denied');
    }

    return true;
}

  private function sendNewOrderNotifications(Order $order, $memberIds, $skipUserId = null)
{
    $ids = collect($memberIds ?? [])
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->filter(fn ($id) => (int) $id !== (int) $skipUserId)
        ->unique()
        ->values();

    if ($ids->isEmpty()) {
        return;
    }

    $members = User::whereIn('id', $ids)->get();

    foreach ($members as $member) {

        OrderNotification::create([
            'user_id' => $member->id,
            'order_id' => $order->id,
            'title' => 'New Order Assigned',
            'message' => 'You have been added to order: ' . $order->name,
            'is_read' => 0,
        ]);

        try {
            Mail::to($member->email)->send(new NewOrderAssignedMail($order, $member));
        } catch (\Throwable $e) {
            report($e);
        }

        FcmService::send(
            $member->fcm_token,
            'New Order Assigned',
            'You have been added to order: ' . $order->name,
            [
                'type' => 'order',
                'order_id' => (string) $order->id,
                'order_name' => (string) $order->name,
            ]
        );
    }
}

    private function sendOrderActivityNotification(Order $order, $skipUserId, $title, $body)
{
    $members = $order->members()
        ->where('users.id', '!=', $skipUserId)
        ->get();

    foreach ($members as $member) {

        OrderNotification::create([
            'user_id' => $member->id,
            'order_id' => $order->id,
            'title' => $title,
            'message' => $body,
            'is_read' => 0,
        ]);

        FcmService::send(
            $member->fcm_token,
            $title,
            $body,
            [
                'type' => 'order_activity',
                'order_id' => (string) $order->id,
                'order_name' => (string) $order->name,
            ]
        );
    }
}

    public function bulkMembers(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'member_ids' => 'required|array',
        ]);

        $orders = Order::whereIn('id', $request->order_ids)->get();

        foreach ($orders as $order) {
            $order->members()->sync($request->member_ids);
        }

        return response()->json([
            'success' => true,
            'message' => 'Members updated successfully'
        ]);
    }

    public function bulkDuplicate(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
        ]);

        $orders = Order::with('members')->whereIn('id', $request->order_ids)->get();

        foreach ($orders as $order) {
            $newOrder = $order->replicate();
            $newOrder->name = $order->name . ' Copy';
            $newOrder->save();

            $newOrder->members()->sync(
                $order->members->pluck('id')->toArray()
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Orders duplicated successfully'
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
        ]);

$orders = Order::whereIn('id', $request->order_ids)->get();

foreach ($orders as $order) {

    $this->logActivity(
        $order->id,
        'deleted',
        auth()->user()->name . ' moved order "' . $order->name . '" to Recycle Bin'
    );

    $order->delete();
}

        return response()->json([
            'success' => true,
            'message' => 'Orders deleted successfully'
        ]);
    }


public function recycleBin()
{
    if (auth()->user()?->role !== 'super_admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $orders = Order::onlyTrashed()
        ->with(['members', 'clients'])
        ->latest('deleted_at')
        ->get()
        ->map(function ($order) {
            $deletedActivity = OrderActivity::with('user:id,name,email')
                ->where('order_id', $order->id)
                ->where('action', 'deleted')
                ->latest()
                ->first();

            $order->deleted_by = $deletedActivity?->user?->name ?? 'Unknown';
            $order->deleted_by_email = $deletedActivity?->user?->email ?? null;

            return $order;
        });

    return response()->json($orders);
}

public function restore($id)
{
    $order = Order::onlyTrashed()->findOrFail($id);
    $order->restore();

    $this->logActivity($order->id, 'restored', 'Order restored from recycle bin');

    return response()->json(['success' => true]);
}

public function forceDelete($id)
{
    $order = Order::onlyTrashed()->findOrFail($id);

    $this->logActivity($order->id, 'permanent_deleted', 'Order permanently deleted');

    $order->forceDelete();

    return response()->json(['success' => true]);
}

public function activities(Order $order)
{
    $this->checkAccess($order);

    return response()->json([
        'activities' => $order->activities()
            ->with('user:id,name,email')
            ->get()
    ]);
}
public function allActivities()
{
    if (auth()->user()?->role !== 'super_admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    return response()->json(
        \App\Models\OrderActivity::with([
            'user:id,name,email',
            'order:id,name,po'
        ])
        ->latest()
        ->get()
    );
}
private function logActivity($orderId, $action, $description = null, $changes = null)
{
    OrderActivity::create([
        'order_id' => $orderId,
        'user_id' => auth()->id(),
        'action' => $action,
        'description' => $description,
        'changes' => $changes,
    ]);
}
public function deleteActivity(\App\Models\OrderActivity $activity)
{
    if (auth()->user()?->role !== 'super_admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $activity->delete();

    return response()->json(['success' => true]);
}
}
