<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background:#f6f7fb; padding:20px; }
        .container { max-width:500px; margin:0 auto; background:white; border-radius:12px; padding:30px; }
        .logo { color:#ff3d57; font-size:24px; font-weight:bold; margin-bottom:20px; }
        .btn { display:inline-block; background:#ff3d57; color:white; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:bold; margin-top:20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">monday</div>
        <h2>You're invited! 🎉</h2>
        <p>Hi <strong>{{ $memberName }}</strong>,</p>
        <p>You have been invited to join <strong>Monday Clone</strong> as a <strong>{{ $role }}</strong>.</p>
        <p>Click the button below to create your account:</p>
        <a href="{{ $inviteLink }}" class="btn">Accept Invitation</a>
        <p style="margin-top:20px; color:#999; font-size:12px;">
            Or copy this link: {{ $inviteLink }}
        </p>
        <p style="color:#999; font-size:12px;">This link will expire in 7 days.</p>
    </div>
</body>
</html>
