@extends('layouts.app')

@section('title', 'Check your email')

@section('content')
<div class="card">
    <h1>Check your email</h1>
    <p>We sent a verification link to your email address. Click the link to verify your account. The link expires in 24 hours.</p>
    <p class="hint">Can't find it? Check your spam folder, or send a new link below.</p>

    <form method="POST" action="{{ route('email.resend') }}" novalidate>
        @csrf
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', session('email')) }}" required>
            <small class="error">{{ $errors->first('email') }}</small>
        </div>
        <button type="submit" class="btn">Resend verification email</button>
    </form>
</div>
@endsection