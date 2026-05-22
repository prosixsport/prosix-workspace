<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body{
            margin:0;
            padding:0;
            background:#f4f5f7;
            font-family:Arial, sans-serif;
        }
        .wrapper{
            padding:30px 15px;
        }
        .container{
            max-width:560px;
            margin:0 auto;
            background:#ffffff;
            border-radius:18px;
            overflow:hidden;
            box-shadow:0 18px 50px rgba(0,0,0,0.12);
        }
        .header{
            background:#000;
            padding:26px 30px;
            text-align:center;
        }
        .brand{
            color:#fff;
            font-size:26px;
            font-weight:900;
            letter-spacing:1px;
        }
        .content{
            padding:34px 34px 28px;
            color:#111827;
        }
        h2{
            margin:0 0 14px;
            font-size:26px;
            color:#111;
        }
        p{
            font-size:15px;
            line-height:1.7;
            color:#4b5563;
            margin:0 0 14px;
        }
        .box{
            background:#f9fafb;
            border:1px solid #e5e7eb;
            border-radius:14px;
            padding:16px;
            margin:20px 0;
        }
        .btn{
            display:inline-block;
            background:#000;
            color:#fff !important;
            padding:14px 28px;
            border-radius:10px;
            text-decoration:none;
            font-weight:800;
            margin-top:12px;
        }
        .btn:hover{
            background:#2e2d4d;
        }
        .link{
            word-break:break-all;
            color:#6b7280;
            font-size:12px;
            margin-top:22px;
        }
        .footer{
            background:#f9fafb;
            padding:18px 30px;
            text-align:center;
            color:#9ca3af;
            font-size:12px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">

            <div class="header">
                <div class="brand">PROSIX</div>
            </div>

            <div class="content">
                <h2>You’re invited to Prosix </h2>

                <p>Hi <strong>{{ $memberName }}</strong>,</p>

                <p>
                    You have been invited to join <strong>Prosix Workspace</strong>
                    as a <strong>{{ $role }}</strong>.
                </p>

                <div class="box">
                    <p style="margin-bottom:8px;">
                        Click the button below to create your account and access your workspace.
                    </p>

                    <a href="{{ $inviteLink }}" class="btn">Accept Invitation</a>
                </div>

                <p class="link">
                    If the button does not work, copy this link:<br>
                    {{ $inviteLink }}
                </p>

                <p style="color:#9ca3af; font-size:12px;">
                    This invitation link will expire in 7 days.
                </p>
            </div>

            <div class="footer">
                © {{ date('Y') }} Prosix. All rights reserved.
            </div>

        </div>
    </div>
</body>
</html>
