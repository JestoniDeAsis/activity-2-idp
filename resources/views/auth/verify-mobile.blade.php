@extends('layouts.app')

@section('title', 'Verify your mobile number')

@section('content')
<div class="card">
    <h1>Verify your mobile number</h1>

    <p>We sent a 6-digit code by SMS to <strong>{{ $maskedMobile }}</strong>. The code is valid for 5 minutes and you have 3 attempts.</p>

    @unless ($hasActive)
        <p class="hint">There is no active code right now. Press "Resend OTP" to get a new one.</p>
    @endunless

    <form id="otp-form" method="POST" action="{{ route('verify-mobile.check') }}" novalidate>
        @csrf

        <div class="field">
            <label for="code">Verification code</label>
            <input type="text" id="code" name="code" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" required>
            @if ($hasActive)
                <small class="hint">Attempts left: {{ $attemptsLeft }}</small>
            @endif
            <small class="error" data-error-for="code">{{ $errors->first('code') }}</small>
        </div>

        <button type="submit" id="submit-btn" class="btn">Verify</button>
    </form>

    <form method="POST" action="{{ route('mobile.resend') }}" class="resend-form">
        @csrf
        <button type="submit" id="resend-btn" class="btn btn-secondary" data-seconds="{{ $resendIn }}" @disabled($resendIn > 0)>Resend OTP</button>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/otp.js') }}"></script>
@endpush