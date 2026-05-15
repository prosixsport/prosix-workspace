<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderChatController;
use App\Http\Controllers\OrderFileController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Profile
    Route::get('/users/{user}/profile', [MemberController::class, 'profile']);
    Route::post('/me/profile', [MemberController::class, 'updateProfile']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::put('/orders/{order}', [OrderController::class, 'update']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);

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
    Route::delete('/members/{id}', [MemberController::class, 'destroy']);

    // Board Members
    Route::get('/boards/{id}/members', [BoardController::class, 'members']);
    Route::post('/boards/{id}/members', [BoardController::class, 'addMember']);
    Route::delete('/boards/{id}/members/{userId}', [BoardController::class, 'removeMember']);

    // Files
    Route::get('/orders/{order}/files', [OrderFileController::class, 'index']);
    Route::post('/orders/{order}/files', [OrderFileController::class, 'store']);
    Route::post('/orders/{order}/chat-files', [OrderFileController::class, 'storeChatFile']);
    Route::delete('/order-files/{file}', [OrderFileController::class, 'destroy']);
});
