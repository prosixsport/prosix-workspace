<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderChatController;
use App\Http\Controllers\OrderFileController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/notifications-test', function () {
    return \App\Models\OrderNotification::latest()->get();
});
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // FCM Token Save
    Route::post('/save-fcm-token', function (Request $request) {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token saved',
        ]);
    });

    // Profile
    Route::get('/users/{user}/profile', [MemberController::class, 'profile']);
    Route::post('/me/profile', [MemberController::class, 'updateProfile']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::put('/orders/{order}', [OrderController::class, 'update']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);

    Route::post('/orders/{order}/mark-read', [OrderController::class, 'markRead']);
    Route::get('/orders/{order}/read-info', [OrderController::class, 'readInfo']);

    Route::post('/orders/bulk-members', [OrderController::class, 'bulkMembers']);
  Route::post('/orders/bulk-duplicate', [OrderController::class, 'bulkDuplicate']);
  Route::post('/orders/bulk-delete', [OrderController::class, 'bulkDelete']);

    // Order Members
    Route::post('/orders/{order}/members', [OrderController::class, 'addMember']);
    Route::delete('/orders/{order}/members/{user}', [OrderController::class, 'removeMember']);

    // Order Chat
    Route::get('/orders/{order}/messages', [OrderChatController::class, 'index']);
    Route::post('/orders/{order}/messages', [OrderChatController::class, 'store']);
    Route::put('/orders/{order}/messages/{message}', [OrderChatController::class, 'update']);
    Route::delete('/orders/{order}/messages/{message}/for-me', [OrderChatController::class, 'deleteForMe']);
    Route::delete('/orders/{order}/messages/{message}/everyone', [OrderChatController::class, 'deleteForEveryone']);
    Route::get('/orders/{order}/messages/unread-count', [OrderChatController::class, 'unreadCount']);
    Route::post('/orders/{order}/messages/mark-read', [OrderChatController::class, 'markRead']);

    // Members
    Route::get('/members', [MemberController::class, 'index']);
    Route::post('/members/invite', [MemberController::class, 'invite']);
    Route::post('/members/{id}/toggle', [MemberController::class, 'toggle']);
    Route::post('/members/{user}/order-create-permission', [MemberController::class, 'toggleOrderCreatePermission']);
    Route::delete('/members/{id}', [MemberController::class, 'destroy']);



    // Files
    Route::get('/orders/{order}/files', [OrderFileController::class, 'index']);
    Route::post('/orders/{order}/files', [OrderFileController::class, 'store']);
    Route::post('/orders/{order}/chat-files', [OrderFileController::class, 'storeChatFile']);
    Route::delete('/order-files/{file}', [OrderFileController::class, 'destroy']);

    Route::middleware('superadmin')->group(function () {
        Route::apiResource('clients', ClientController::class);
        Route::apiResource('invoices', InvoiceController::class);
    });

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);

});
