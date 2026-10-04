@extends('layouts.app')

@section('title', $status === 'success' ? 'Email verified' : 'Link not valid')

@section('content')
<div class="card">
    @if ($status === 'success')
        <h1>Email verified</h1>
        <p>Email verified. You can now log in.</p>
        <p><a class="btn" href="/login">Go to login</a></p>
    @else
        <h1>Link not valid</h1>
        <p>This verification link is invalid or has expired. Enter your email to get a new one.</p>

        <form method="POST" action="{{ route('email.resend') }}" novalidate>
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                <small class="error">{{ $errors->first('email') }}</small>
            </div>
            <button type="submit" class="btn">Resend verification email</button>
        </form>
    @endif
</div>
@endsection