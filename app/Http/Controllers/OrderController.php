<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Mail\NewOrderAssignedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $orders = Order::with(['members'])
            ->when($user && !in_array($user->role, ['super_admin']), function ($q) use ($user) {
                $q->whereHas('members', function ($m) use ($user) {
                    $m->where('users.id', $user->id);
                });
            })
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated. Please login again.'
            ], 401);
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
            'created_by'       => $user->id,
        ]);

        $order->members()->syncWithoutDetaching([
            $user->id => [
                'role' => $user->role === 'super_admin' ? 'admin' : 'member'
            ]
        ]);

        foreach ($request->member_ids ?? [] as $memberId) {
            $order->members()->syncWithoutDetaching([
                $memberId => [
                    'role' => 'member'
                ]
            ]);
        }

        $this->sendNewOrderEmails($order, $request->member_ids ?? [], $user->id);

        return response()->json([
            'success' => true,
            'order'   => $order->load('members')
        ]);
    }

    public function show(Order $order)
    {
        $this->checkAccess($order);

        return response()->json(
            $order->load(['members', 'messages.user'])
        );
    }

    public function update(Request $request, Order $order)
    {
        $this->checkAccess($order);

        $user = auth()->user();
        $isSuperAdmin = $user && $user->role === 'super_admin';

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
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        $oldMemberIds = $order->members()->pluck('users.id')->map(fn ($id) => (int) $id)->toArray();

        if ($isSuperAdmin) {
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
                    $syncData[auth()->id()] = [
                        'role' => auth()->user()?->role === 'super_admin' ? 'admin' : 'member'
                    ];
                }

                $order->members()->sync($syncData);

                $newMemberIds = collect(array_keys($syncData))
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => !in_array($id, $oldMemberIds))
                    ->filter(fn ($id) => $id !== (int) auth()->id())
                    ->unique()
                    ->values();

                $this->sendNewOrderEmails($order, $newMemberIds, auth()->id());
            }
        } else {
            // Members can update only status + tracking number.
            $order->update($request->only([
                'status',
                'status_color',
                'trk',
            ]));
        }

        return response()->json([
            'success' => true,
            'order' => $order->load('members')
        ]);
    }

    public function destroy(Order $order)
    {
        $this->checkAccess($order);

        $order->delete();

        return response()->json([
            'success' => true
        ]);
    }

    public function addMember(Request $request, Order $order)
    {
        $this->checkAccess($order);

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

            $this->sendNewOrderEmails($order, [$request->user_id], auth()->id());
        }

        return response()->json([
            'success' => true,
            'members' => $order->members()->get()
        ]);
    }

    public function removeMember(Order $order, User $user)
    {
        $this->checkAccess($order);

        $order->members()->detach($user->id);

        return response()->json([
            'success' => true
        ]);
    }

    private function checkAccess($order)
    {
        $user = auth()->user();

        if ($user && $user->role === 'super_admin') {
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

    private function sendNewOrderEmails(Order $order, $memberIds, $skipUserId = null)
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
            Mail::to($member->email)->send(new NewOrderAssignedMail($order, $member));
        }
    }
}
