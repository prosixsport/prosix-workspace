<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderWorkSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $isSuperAdmin = $user->role === 'super_admin';

        $orders = Order::query()
            ->with([
                'members:id,name,role,profile_photo',
                'activeWorkSession.user:id,name,role,profile_photo',
            ])
            ->when(!$isSuperAdmin && $user->role === 'client', function ($query) use ($user) {
                $query->whereHas('clients', fn ($clients) =>
                    $clients->where('clients.user_id', $user->id)
                );
            })
            ->when(!$isSuperAdmin && $user->role !== 'client', function ($query) use ($user) {
                $query->where(function ($orders) use ($user) {
                    $orders->where('created_by', $user->id)
                        ->orWhereHas('members', fn ($members) =>
                            $members->where('users.id', $user->id)
                        );
                });
            })
            ->latest()
            ->get();

        $stats = [
            'totalOrders' => $orders->count(),

            'pending' => $orders
                ->filter(fn ($order) =>
                    $this->statusContains(
                        $order->status,
                        ['pending', 'design']
                    )
                )
                ->count(),

            'inProduction' => $orders
                ->filter(fn ($order) =>
                    $this->statusContains(
                        $order->status,
                        ['production', 'progress', 'packing']
                    )
                )
                ->count(),

            'completed' => $orders
                ->filter(fn ($order) =>
                    $this->statusContains(
                        $order->status,
                        ['completed']
                    )
                )
                ->count(),

            'shipped' => $orders
                ->filter(fn ($order) =>
                    $this->statusContains(
                        $order->status,
                        ['shipped']
                    )
                )
                ->count(),

            'delivered' => $orders
                ->filter(fn ($order) =>
                    $this->statusContains(
                        $order->status,
                        ['delivered']
                    )
                )
                ->count(),
        ];

        $recentOrders = $orders
            ->take(8)
            ->values()
            ->map(fn ($order) => [
                'id' => $order->id,
                'name' => $order->name,
                'po' => $order->po,
                'status' => $order->status,
                'status_color' => $order->status_color,
                'created_at' => $order->created_at,
            ]);

        return response()->json([
            'stats' => $stats,
            'recent_orders' => $recentOrders,
            'designer_performance' =>
                $this->designerPerformanceData($user, $orders),
        ]);
    }

    private function designerPerformanceData(User $viewer, $visibleOrders)
    {
        $designers = User::query()
            ->whereIn('role', [
                'super_admin',
                'admin',
                'member',
            ])
            ->when($viewer->role !== 'super_admin', fn ($query) =>
                $query->where('id', $viewer->id)
            )
            ->orderByRaw("
                CASE
                    WHEN role = 'super_admin' THEN 1
                    WHEN role = 'admin' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'role',
                'profile_photo',
            ]);

        if ($designers->isEmpty()) {
            return collect();
        }

        $designerIds = $designers->pluck('id');
        $visibleOrderIds = $visibleOrders->pluck('id');

        $sessions = OrderWorkSession::query()
            ->with('order:id,name,po,status')
            ->whereIn('user_id', $designerIds)
            ->whereIn('order_id', $visibleOrderIds)
            ->orderByDesc('started_at')
            ->get();

        $activities = OrderActivity::query()
            ->with('order:id,name,po,status')
            ->whereIn('order_id', $visibleOrderIds)
            ->orderByDesc('created_at')
            ->get();

        $statusOrdersByUser = [];

        foreach ($visibleOrders as $order) {
            $category = $this->statusCategory($order->status);

            $matchingActivity = $activities
                ->where('order_id', $order->id)
                ->first(fn ($activity) =>
                    $activity->user_id &&
                    $this->activityMovedTo($activity, $category)
                );

            $latestSession = $sessions
                ->where('order_id', $order->id)
                ->first();

            $ownerId = $category === 'in_production'
                ? $latestSession?->user_id
                : ($matchingActivity?->user_id ?: $latestSession?->user_id);

            if (!$ownerId || !$designerIds->contains((int) $ownerId)) {
                continue;
            }

            $statusOrdersByUser[(int) $ownerId][$category][] = [
                'order_id' => $order->id,
                'order_name' => $order->name ?: 'Deleted Order',
                'po' => $order->po,
                'status' => $order->status,
                'status_color' => $order->status_color,
                'started_at' => $latestSession?->started_at,
                'changed_at' => $matchingActivity?->created_at ?: $order->updated_at,
            ];
        }

        return $designers->map(function ($designer) use (
            $sessions,
            $activities
        ) {
            $designerSessions = $sessions
                ->where('user_id', $designer->id)
                ->values();

            $designerActivities = $activities
                ->where('user_id', $designer->id)
                ->values();

            $completedSessions = $designerSessions
                ->whereNotNull('ended_at');

            $totalMinutes = (int) round(
                $completedSessions->sum(function ($session) {
                    return Carbon::parse($session->started_at)
                        ->diffInMinutes(
                            Carbon::parse($session->ended_at)
                        );
                })
            );

            $workedOrderIds = $designerSessions
                ->pluck('order_id')
                ->filter()
                ->unique()
                ->values();

            $categoryOrders = $statusOrdersByUser[(int) $designer->id] ?? [];
            $inProductionOrders = collect($categoryOrders['in_production'] ?? [])->values();
            $completedOrders = collect($categoryOrders['completed'] ?? [])->values();
            $shippedOrders = collect($categoryOrders['shipped'] ?? [])->values();
            $deliveredOrders = collect($categoryOrders['delivered'] ?? [])->values();

            $currentlyWorking = $designerSessions
                ->whereNull('ended_at')
                ->values();

            $averageMinutes = $completedSessions->count() > 0
                ? (int) round(
                    $totalMinutes /
                    $completedSessions->count()
                )
                : 0;

            $recentRecord = $designerSessions
                ->take(8)
                ->map(function ($session) {
                    $minutes = null;

                    if ($session->ended_at) {
                        $minutes = (int) Carbon::parse(
                            $session->started_at
                        )->diffInMinutes(
                            Carbon::parse(
                                $session->ended_at
                            )
                        );
                    }

                    return [
                        'id' => $session->id,
                        'order_id' => $session->order_id,
                        'order_name' =>
                            $session->order?->name ??
                            'Deleted Order',
                        'po' => $session->order?->po,
                        'status' =>
                            $session->order?->status,
                        'started_at' =>
                            $session->started_at,
                        'ended_at' =>
                            $session->ended_at,
                        'minutes' => $minutes,
                        'is_working' =>
                            is_null($session->ended_at),
                    ];
                })
                ->values();

            return [
                'id' => $designer->id,
                'name' => $designer->name,
                'email' => $designer->email,
                'role' => $designer->role,
                'profile_photo_url' =>
                    $designer->profile_photo_url,

                'currently_working' =>
                    $currentlyWorking->count(),

                'currently_working_orders' =>
                    $currentlyWorking
                        ->map(fn ($session) => [
                            'order_id' =>
                                $session->order_id,
                            'order_name' =>
                                $session->order?->name ??
                                'Deleted Order',
                            'started_at' =>
                                $session->started_at,
                        ])
                        ->values(),

                'in_production_orders' => $inProductionOrders,
                'in_production_count' => $inProductionOrders->count(),
                'completed_order_list' => $completedOrders,
                'completed_orders' => $completedOrders->count(),
                'shipped_order_list' => $shippedOrders,
                'shipped_orders' => $shippedOrders->count(),
                'delivered_order_list' => $deliveredOrders,
                'delivered_orders' => $deliveredOrders->count(),

                'total_minutes' =>
                    $totalMinutes,

                'average_minutes' =>
                    $averageMinutes,

                'recent_record' =>
                    $recentRecord,
            ];
        })->values();
    }

    private function activityMovedTo(
        OrderActivity $activity,
        string $status
    ): bool {
        $action = strtolower(
            (string) $activity->action
        );

        $description = strtolower(
            (string) $activity->description
        );

        $changes = is_array($activity->changes)
            ? $activity->changes
            : [];

        $newStatus = strtolower(
            (string) (
                $changes['new'] ??
                $changes['status']['new'] ??
                $changes['status']['to'] ??
                $changes['to_status'] ??
                ''
            )
        );

        return (
            $this->statusCategory($newStatus) === $status ||
            str_contains($action, $status) ||
            str_contains($description, $status)
        );
    }

    private function statusCategory($status): string
    {
        $status = strtolower(trim((string) $status));

        if (str_contains($status, 'delivered')) return 'delivered';
        if (str_contains($status, 'shipped')) return 'shipped';
        if (str_contains($status, 'completed')) return 'completed';

        return 'in_production';
    }

    private function statusContains(
        $status,
        array $needles
    ): bool {
        $cleanStatus = strtolower(
            (string) $status
        );

        foreach ($needles as $needle) {
            if (
                str_contains(
                    $cleanStatus,
                    strtolower($needle)
                )
            ) {
                return true;
            }
        }

        return false;
    }
}
