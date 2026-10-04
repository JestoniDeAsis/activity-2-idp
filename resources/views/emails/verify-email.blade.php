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

            <p>Thank you for registering with {{ config('app.name') }}. We are thrilled to welcome you to our community.</p>

            <p>To ensure the security of your account and complete your registration, please verify your email address by clicking the secure link below:</p>

            <p style="text-align:center;margin:28px 0;">
                <a href="{{ $verifyUrl }}" style="background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:6px;font-weight:bold;display:inline-block;">Verify My Email Address</a>
            </p>

            <p>If you did not initiate this request, please disregard this message. This link will expire in 24 hours for your protection.</p>

            <p style="margin-bottom:0;">Warm regards,<br>The {{ config('app.name') }} Security Team</p>
        </div>
    </div>
</body>
</html>