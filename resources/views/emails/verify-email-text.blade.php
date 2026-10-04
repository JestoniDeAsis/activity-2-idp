Dear {!! $user->first_name !!},

Thank you for registering with {!! config('app.name') !!}. We are thrilled to welcome you to our community.

To ensure the security of your account and complete your registration, please verify your email address by clicking the secure link below:

Verify My Email Address: {!! $verifyUrl !!}

If you did not initiate this request, please disregard this message. This link will expire in 24 hours for your protection.

Warm regards,
The {!! config('app.name') !!} Security Team