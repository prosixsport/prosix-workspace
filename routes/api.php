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
use App\Http\Controllers\PlaceOrderController;
use App\Http\Controllers\TeamStoreOrderController;
use App\Http\Controllers\ArtworkRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [
    AuthController::class,
    'login'
]);

Route::post('/register', [
    AuthController::class,
    'register'
]);

Route::get('/notifications-test', function () {
    return \App\Models\OrderNotification::latest()->get();
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [
        DashboardController::class,
        'index'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::post('/logout', [
        AuthController::class,
        'logout'
    ]);

    Route::get('/me', [
        AuthController::class,
        'me'
    ]);

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

    /*
    |--------------------------------------------------------------------------
    | Prosix Website Orders
    |--------------------------------------------------------------------------
    |
    | Factory Orders in routes se separate hain.
    | Factory Orders CRM ke andar manually create honge.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Place Orders
    |--------------------------------------------------------------------------
    */

    Route::prefix('place-orders')->group(function () {

        Route::get('/', [
            PlaceOrderController::class,
            'index'
        ]);

        Route::get('/unread-count', [
            PlaceOrderController::class,
            'unreadCount'
        ]);

        // Status routes MUST stay before /{id}
        Route::get('/statuses', [
            PlaceOrderController::class,
            'statuses'
        ]);

        Route::post('/statuses', [
            PlaceOrderController::class,
            'storeStatus'
        ]);

        Route::put('/statuses/{id}', [
            PlaceOrderController::class,
            'updateStatusDefinition'
        ]);

        Route::delete('/statuses/{id}', [
            PlaceOrderController::class,
            'destroyStatus'
        ]);

        Route::post('/{id}/mark-read', [
            PlaceOrderController::class,
            'markRead'
        ]);

        Route::put('/{id}', [
            PlaceOrderController::class,
            'update'
        ]);

    });

    /*
    |--------------------------------------------------------------------------
    | TeamStore Orders
    |--------------------------------------------------------------------------
    */

    Route::get('/teamstore-orders', [
        TeamStoreOrderController::class,
        'index'
    ]);

    Route::get('/teamstore-orders/unread-count', [
        TeamStoreOrderController::class,
        'unreadCount'
    ]);

    Route::post('/teamstore-orders/{id}/mark-read', [
        TeamStoreOrderController::class,
        'markRead'
    ]);

    Route::put('/teamstore-orders/{id}', [
        TeamStoreOrderController::class,
        'update'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Artwork Requests - Prosix.com Sync
    |--------------------------------------------------------------------------
    |
    | CRM frontend in routes ko call karega.
    | ArtworkRequestController in requests ko Prosix.com ke
    | /api/crm/artwork-requests endpoints par forward karega.
    |
    */

    Route::prefix('artwork-requests')->group(function () {

        Route::get('/', [
            ArtworkRequestController::class,
            'index'
        ]);

        Route::get('/unread-count', [
            ArtworkRequestController::class,
            'unreadCount'
        ]);

        Route::post('/{id}/mark-read', [
            ArtworkRequestController::class,
            'markRead'
        ]);

    });

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    Route::get('/users/{user}/profile', [
        MemberController::class,
        'profile'
    ]);

    Route::post('/me/profile', [
        MemberController::class,
        'updateProfile'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Factory Orders
    |--------------------------------------------------------------------------
    |
    | Important:
    | Special routes hamesha /orders/{order} se pehle honi chahiye.
    |
    */

    Route::get('/orders/recycle-bin', [
        OrderController::class,
        'recycleBin'
    ]);

    Route::get('/order-activities', [
        OrderController::class,
        'allActivities'
    ]);

    Route::post('/orders/bulk-members', [
        OrderController::class,
        'bulkMembers'
    ]);

    Route::post('/orders/bulk-duplicate', [
        OrderController::class,
        'bulkDuplicate'
    ]);

    Route::post('/orders/bulk-delete', [
        OrderController::class,
        'bulkDelete'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Factory Order Working Status
    |--------------------------------------------------------------------------
    */

    Route::post('/orders/{order}/claim', [
        OrderController::class,
        'claim'
    ]);

    Route::post('/orders/{order}/release', [
        OrderController::class,
        'release'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Main Factory Order Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/orders', [
        OrderController::class,
        'index'
    ]);

    Route::post('/orders', [
        OrderController::class,
        'store'
    ]);

    Route::get('/orders/{order}', [
        OrderController::class,
        'show'
    ]);

    Route::put('/orders/{order}', [
        OrderController::class,
        'update'
    ]);

    Route::delete('/orders/{order}', [
        OrderController::class,
        'destroy'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Factory Order Read Information
    |--------------------------------------------------------------------------
    */

    Route::post('/orders/{order}/mark-read', [
        OrderController::class,
        'markRead'
    ]);

    Route::get('/orders/{order}/read-info', [
        OrderController::class,
        'readInfo'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Factory Order Recycle Bin Actions
    |--------------------------------------------------------------------------
    */

    Route::post('/orders/{id}/restore', [
        OrderController::class,
        'restore'
    ]);

    Route::delete('/orders/{id}/force-delete', [
        OrderController::class,
        'forceDelete'
    ]);

    Route::get('/orders/{order}/activities', [
        OrderController::class,
        'activities'
    ]);

    Route::delete('/order-activities/{activity}', [
        OrderController::class,
        'deleteActivity'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Factory Order Members
    |--------------------------------------------------------------------------
    */

    Route::post('/orders/{order}/members', [
        OrderController::class,
        'addMember'
    ]);

    Route::delete('/orders/{order}/members/{user}', [
        OrderController::class,
        'removeMember'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Factory Order Chat
    |--------------------------------------------------------------------------
    */

    Route::get('/orders/{order}/messages', [
        OrderChatController::class,
        'index'
    ]);

    Route::post('/orders/{order}/messages', [
        OrderChatController::class,
        'store'
    ]);

    Route::put('/orders/{order}/messages/{message}', [
        OrderChatController::class,
        'update'
    ]);

    Route::delete('/orders/{order}/messages/{message}/for-me', [
        OrderChatController::class,
        'deleteForMe'
    ]);

    Route::delete('/orders/{order}/messages/{message}/everyone', [
        OrderChatController::class,
        'deleteForEveryone'
    ]);

    Route::get('/orders/{order}/messages/unread-count', [
        OrderChatController::class,
        'unreadCount'
    ]);

    Route::post('/orders/{order}/messages/mark-read', [
        OrderChatController::class,
        'markRead'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Factory Order Files
    |--------------------------------------------------------------------------
    */

    Route::get('/orders/{order}/files', [
        OrderFileController::class,
        'index'
    ]);

    Route::post('/orders/{order}/files', [
        OrderFileController::class,
        'store'
    ]);

    Route::post('/orders/{order}/chat-files', [
        OrderFileController::class,
        'storeChatFile'
    ]);

    Route::delete('/order-files/{file}', [
        OrderFileController::class,
        'destroy'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Members
    |--------------------------------------------------------------------------
    */

    Route::get('/members', [
        MemberController::class,
        'index'
    ]);

    Route::post('/members/invite', [
        MemberController::class,
        'invite'
    ]);

    Route::post('/members/{id}/toggle', [
        MemberController::class,
        'toggle'
    ]);

    Route::post('/members/{user}/order-create-permission', [
        MemberController::class,
        'toggleOrderCreatePermission'
    ]);

    Route::delete('/members/{id}', [
        MemberController::class,
        'destroy'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Clients
    |--------------------------------------------------------------------------
    */

    Route::get('/clients', [
        ClientController::class,
        'index'
    ]);

    Route::middleware('superadmin')->group(function () {

        Route::post('/clients', [
            ClientController::class,
            'store'
        ]);

        Route::get('/clients/{client}', [
            ClientController::class,
            'show'
        ]);

        Route::put('/clients/{client}', [
            ClientController::class,
            'update'
        ]);

        Route::delete('/clients/{client}', [
            ClientController::class,
            'destroy'
        ]);

        Route::apiResource(
            'invoices',
            InvoiceController::class
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    Route::get('/notifications', [
        NotificationController::class,
        'index'
    ]);

    Route::post('/notifications/{id}/read', [
        NotificationController::class,
        'markRead'
    ]);

    Route::post('/notifications/mark-all-read', [
        NotificationController::class,
        'markAllRead'
    ]);
});
