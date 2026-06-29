<?php

namespace App\Events;

use App\Models\OrderMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public array $message;

    public function __construct(OrderMessage $message)
    {
        $message->load([
            'user',
            'replyTo.user',
        ]);

        $this->message = [
            'id' => $message->id,
            'order_id' => $message->order_id,
            'user_id' => $message->user_id,
            'message' => $message->message,
            'reply_to_id' => $message->reply_to_id,
            'reply_to' => $message->replyTo ? [
                'id' => $message->replyTo->id,
                'message' => $message->replyTo->message,
                'user' => [
                    'id' => $message->replyTo->user?->id,
                    'name' => $message->replyTo->user?->name,
                    'email' => $message->replyTo->user?->email,
                    'profile_photo_url' => $message->replyTo->user?->profile_photo_url,
                ],
            ] : null,

            'deleted_for' => $message->deleted_for,
            'deleted_everyone_at' => $message->deleted_everyone_at,
            'edited_at' => $message->edited_at,
            'created_at' => $message->created_at,
            'updated_at' => $message->updated_at,
            'is_seen' => false,
            'reads' => [],
            'user' => [
                'id' => $message->user?->id,
                'name' => $message->user?->name,
                'email' => $message->user?->email,
                'profile_photo_url' => $message->user?->profile_photo_url,
            ],
        ];
    }

    public function broadcastOn(): Channel
    {
        return new Channel('order.' . $this->message['order_id']);
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
