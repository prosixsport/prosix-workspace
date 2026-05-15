<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardItem;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $boards = Board::where('created_by', $user->id)
            ->withCount('items')
            ->latest()
            ->get();

        $totalTasks = BoardItem::whereHas('group.board', function ($q) use ($user) {
            $q->where('created_by', $user->id);
        })->count();

        $inProgress = BoardItem::whereHas('group.board', function ($q) use ($user) {
            $q->where('created_by', $user->id);
        })->where('status', 'working_on_it')->count();

        $completed = BoardItem::whereHas('group.board', function ($q) use ($user) {
            $q->where('created_by', $user->id);
        })->where('status', 'done')->count();

        $myTasks = BoardItem::with(['group.board'])
            ->where('assigned_to', $user->id)
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'stats' => [
                'totalBoards' => $boards->count(),
                'totalTasks'  => $totalTasks,
                'inProgress'  => $inProgress,
                'completed'   => $completed,
            ],
            'recent_boards' => $boards->take(5),
            'my_tasks'      => $myTasks,
        ]);
    }
}
