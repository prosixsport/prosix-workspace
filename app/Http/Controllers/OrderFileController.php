<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderFile;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class OrderFileController extends Controller
{
    public function index(Order $order)
    {
        $this->checkAccess($order);

        return response()->json(
            $order->files()->with('user')->latest()->get()
        );
    }

    public function store(Request $request, Order $order)
    {
        $user = auth()->user();

        $this->checkAccess($order);

        if ($user->role !== 'super_admin' && !$user->can_create_orders && $user->role !== 'client') {
            abort(403, 'You do not have permission to upload files.');
        }

        $request->validate([
            'card_type' => 'required|string',
            'files' => 'required|array',
            'files.*' => 'required|file|max:51200',
        ]);

        $saved = [];

        foreach ($request->file('files', []) as $file) {
            if (!$file) continue;

            $path = $file->store("order-files/{$order->id}/{$request->card_type}", 'public');

            $saved[] = OrderFile::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'card_type' => $request->card_type,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ])->load('user');
        }

        if (count($saved) > 0) {
            $this->sendFileUploadNotification(
                $order,
                $user->id,
                $user->name . ' added ' . count($saved) . ' new file/image in order: ' . $order->name,
                'New File Added'
            );

        if ($user->role === 'client') {
    $filesHtml = collect($saved)->map(function ($file) {
        $url = asset('storage/' . $file->file_path);
        $name = e($file->original_name);
        $isImage = str_starts_with($file->mime_type, 'image/');

        if ($isImage) {
            return "
                <div style='margin:12px 0;padding:10px;border:1px solid #ddd;border-radius:8px;'>
                    <strong>{$name}</strong><br>
                    <img src='{$url}' style='max-width:320px;margin-top:8px;border-radius:8px;border:1px solid #ccc;'>
                    <br>
                    <a href='{$url}' target='_blank'>Open / Download</a>
                </div>
            ";
        }

        return "
            <div style='margin:12px 0;padding:10px;border:1px solid #ddd;border-radius:8px;'>
                <strong>{$name}</strong><br>
                <a href='{$url}' target='_blank'>Open / Download File</a>
            </div>
        ";
    })->implode('');

    Mail::html(
        "
        <h2>Client Uploaded Files</h2>
        <p><strong>Client:</strong> {$user->name}</p>
        <p><strong>Email:</strong> {$user->email}</p>
        <p><strong>Order:</strong> {$order->name}</p>
        <p><strong>PO:</strong> {$order->po}</p>
        <p><strong>Card:</strong> {$request->card_type}</p>

        <hr>

        <h3>Uploaded Files</h3>
        {$filesHtml}
        ",
        function ($message) use ($order) {
            $message->to('prosixsports@gmail.com')
                ->subject('Client Uploaded Files: ' . $order->name);
        }
    );
}
        }

        return response()->json([
            'success' => true,
            'files' => $saved,
        ]);
    }
    public function storeChatFile(Request $request, Order $order)
    {
        $user = auth()->user();

        if ($user->role === 'client') {
            abort(403, 'Clients cannot upload chat files.');
        }

        $this->checkAccess($order);

        $request->validate([
            'files' => 'required',
            'files.*' => 'file|max:51200',
        ]);

        $saved = [];

        foreach ((array) $request->file('files') as $file) {
            if (!$file) continue;

            $path = $file->store("order-files/{$order->id}/chat", 'public');

            $saved[] = OrderFile::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'card_type' => 'chat_files',
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ])->load('user');
        }

        if (count($saved) > 0) {
            $this->sendFileUploadNotification(
                $order,
                $user->id,
                $user->name . ' added ' . count($saved) . ' chat file/image in order: ' . $order->name,
                'New Chat File Added'
            );
        }

        return response()->json([
            'success' => true,
            'files' => $saved,
        ]);
    }

    public function destroy(OrderFile $file)
    {
        $user = auth()->user();

        if ($user->role === 'client') {
            abort(403, 'Clients cannot delete files.');
        }

        $order = Order::findOrFail($file->order_id);
        $this->checkAccess($order);

        $isOwner = (int) $file->user_id === (int) $user->id;
        $isSuperAdmin = $user->role === 'super_admin';

        if (!$isOwner && !$isSuperAdmin) {
            abort(403, 'You can delete only your own file.');
        }

        Storage::disk('public')->delete($file->file_path);
        $file->delete();

        return response()->json(['success' => true]);
    }

    private function sendFileUploadNotification(Order $order, $skipUserId, $body, $title = 'New File Added')
    {
        $members = $order->members()
            ->where('users.id', '!=', $skipUserId)
            ->get();

        foreach ($members as $member) {
            FcmService::send(
                $member->fcm_token,
                $title,
                $body,
                [
                    'type' => 'order_file',
                    'order_id' => (string) $order->id,
                    'order_name' => (string) $order->name,
                ]
            );
        }
    }

    private function checkAccess(Order $order)
    {
        $user = auth()->user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if ($user->role === 'super_admin') {
            return true;
        }

        if ($user->role === 'client') {
            $allowed = $order->clients()
                ->where('clients.user_id', $user->id)
                ->exists();

            if (!$allowed) {
                abort(403, 'Access denied');
            }

            return true;
        }

        $allowed = $order->members()
            ->where('users.id', $user->id)
            ->exists();

        if (!$allowed) {
            abort(403, 'Access denied');
        }

        return true;
    }
}
