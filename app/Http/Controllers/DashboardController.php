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

        $orders = Order::query()
            ->with([
                'members:id,name,role,profile_photo',
                'activeWorkSession.user:id,name,role,profile_photo',
            ])
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
                $this->designerPerformanceData(),
        ]);
    }

    private function designerPerformanceData()
    {
        $designers = User::query()
            ->whereIn('role', [
                'super_admin',
                'admin',
                'member',
            ])
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

        $sessions = OrderWorkSession::query()
            ->with('order:id,name,po,status')
            ->orderByDesc('started_at')
            ->get();

        $activities = OrderActivity::query()
            ->with('order:id,name,po,status')
            ->orderByDesc('created_at')
            ->get();

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

            $completedOrders = $designerActivities
                ->filter(
                    fn ($activity) =>
                        $this->activityMovedTo(
                            $activity,
                            'completed'
                        )
                )
                ->pluck('order_id')
                ->filter()
                ->unique()
                ->count();

            $forwardedOrders = $designerActivities
                ->filter(
                    fn ($activity) =>
                        str_contains(
                            strtolower(
                                (string) $activity->action
                            ),
                            'forward'
                        )
                )
                ->count();

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

                'total_worked_orders' =>
                    $workedOrderIds->count(),

                'completed_orders' =>
                    $completedOrders,

                'forwarded_orders' =>
                    $forwardedOrders,

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
                $changes['status']['new'] ??
                $changes['status']['to'] ??
                $changes['to_status'] ??
                ''
            )
        );

        return (
            str_contains($action, $status) ||
            str_contains($description, $status) ||
            $newStatus === strtolower($status)
        );
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
