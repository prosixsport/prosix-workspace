<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Order Assigned</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f6f7fb; padding:20px;">
    <div style="max-width:600px; margin:auto; background:#fff; padding:24px; border-radius:12px;">
        <h2 style="color:#172b4d;">New Order Assigned</h2>

        <p>Hi {{ $member->name }},</p>

        <p>You have been added to a new order. Please login and check the details.</p>

        <p><strong>Order:</strong> {{ $order->name }}</p>
        <p><strong>P.O #:</strong> {{ $order->po }}</p>
        <p><strong>Status:</strong> {{ $order->status }}</p>

        <a href="{{ url('/orders') }}"
           style="display:inline-block; background:#6161ff; color:#fff; padding:10px 16px; border-radius:8px; text-decoration:none;">
            View Order
        </a>
    </div>
</body>
</html>
