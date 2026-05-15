<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewOrderAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $member;

    public function __construct(Order $order, User $member)
    {
        $this->order = $order;
        $this->member = $member;
    }

    public function build()
    {
        return $this->subject('New Order Assigned: ' . $this->order->name)
            ->view('emails.new-order-assigned');
    }
}
