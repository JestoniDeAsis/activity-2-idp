Dear {!! $user->first_name !!},

SECURITY ALERT: we detected 3 failed login attempts on your {!! config('app.name') !!} account, so we locked it to keep your information safe.

To unlock your account, please use the secure link below:

Unlock My Account: {!! $unlockUrl !!}

For your protection, the unlock link only works after a 2-minute cooling period that starts when your account was locked. If you open it too early, wait a moment and try again. The link expires in 1 hour.

If you did not make these login attempts, you can ignore this message. Your account will stay locked until the link is used.

Warm regards,
The {!! config('app.name') !!} Security Team