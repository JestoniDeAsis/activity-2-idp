@extends('layouts.app')

@section('title', 'Log in')

@section('content')
<div class="card">
    <h1>Log in</h1>

    @if ($errors->has('login'))
        <div class="alert alert-error">{{ $errors->first('login') }}</div>
    @endif

    <form id="login-form" method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
            <small class="error" data-error-for="email">{{ $errors->first('email') }}</small>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <div class="password-group">
                <input type="password" id="password" name="password" maxlength="255" autocomplete="current-password" required>
                <button type="button" class="toggle-password" data-toggle-for="password" aria-controls="password" aria-pressed="false">Show</button>
            </div>
            <small class="error" data-error-for="password">{{ $errors->first('password') }}</small>
        </div>

        <button type="submit" id="submit-btn" class="btn">Log in</button>
    </form>

    <p class="form-links">No account yet? <a href="{{ route('register') }}">Create an account</a></p>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/login.js') }}"></script>
@endpush