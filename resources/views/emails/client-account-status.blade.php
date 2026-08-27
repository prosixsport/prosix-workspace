<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>Prosix CRM</title>
</head>

<body style="margin:0; padding:30px; background:#f5f5f5; font-family:Arial,sans-serif;">

<div
    style="
        max-width:620px;
        margin:0 auto;
        padding:30px;
        background:#ffffff;
        border-radius:14px;
        box-shadow:0 10px 30px rgba(0,0,0,0.08);
    "
>
    <h2 style="margin:0 0 20px; color:#111111;">
        Prosix CRM
    </h2>

    <p style="color:#333333; line-height:1.7;">
        Hello {{ $client->name }},
    </p>

    @if ($state === 'added')
        <p style="color:#333333; line-height:1.7;">
            You have been added to Prosix CRM.
            Open the login page and enter your registered email address.
            The password you enter during your first login will be saved
            as your permanent account password.
        </p>

    @elseif ($state === 'pending')
        <p style="color:#333333; line-height:1.7;">
            Your Prosix CRM account request has been received.
            Please allow up to 24 hours for review.
            You will receive another email when your account is approved.
        </p>

    @elseif ($state === 'approved')
        <p style="color:#333333; line-height:1.7;">
            Your Prosix CRM account has been approved.
            You can now log in using your registered email address
            and the password you selected during signup.
        </p>

    @elseif ($state === 'rejected')
        <p style="color:#333333; line-height:1.7;">
            Your Prosix CRM account request has been rejected or blocked.
            You cannot log in at this time.
            Please contact Prosix support if you believe this is a mistake.
        </p>
    @endif

    <p style="margin:28px 0 8px;">
        <a
            href="{{ config('app.frontend_url', config('app.url')) . '/login' }}"
            style="
                display:inline-block;
                padding:13px 20px;
                border-radius:9px;
                background:#000000;
                color:#ffffff;
                font-weight:bold;
                text-decoration:none;
            "
        >
            Log In to Prosix CRM
        </a>
    </p>

    <p style="margin-top:28px; color:#777777; font-size:12px;">
        This is an automated email from Prosix CRM.
    </p>
</div>

</body>
</html>
