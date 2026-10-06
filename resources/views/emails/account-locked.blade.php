<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#f3f5f9;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <div style="max-width:560px;margin:0 auto;padding:24px;">
        <div style="background:#ffffff;border-radius:10px;padding:28px;line-height:1.6;">
            <p style="margin-top:0;">Dear {{ $user->first_name }},</p>

            <p><strong>Security Alert:</strong> we detected 3 failed login attempts on your {{ config('app.name') }} account, so we locked it to keep your information safe.</p>

            <p>To unlock your account, please use the secure link below:</p>

            <p style="text-align:center;margin:28px 0;">
                <a href="{{ $unlockUrl }}" style="background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:6px;font-weight:bold;display:inline-block;">Unlock My Account</a>
            </p>

            <p>For your protection, the unlock link only works after a 2-minute cooling period that starts when your account was locked. If you open it too early, wait a moment and try again. The link expires in 24 hour.</p>

            <p>If you did not make these login attempts, you can ignore this message. Your account will stay locked until the link is used.</p>

            <p style="margin-bottom:0;">Warm regards,<br>The {{ config('app.name') }} Security Team</p>
        </div>
    </div>
</body>
</html>