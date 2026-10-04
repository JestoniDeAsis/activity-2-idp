@extends('layouts.app')

@section('title', match ($status) {
    'success' => 'Account unlocked',
    'wait' => 'Please wait',
    default => 'Link not valid',
})

@section('content')
<div class="card">
    @if ($status === 'success')
        <h1>Account unlocked</h1>
        <p>Your account is unlocked. You can now log in.</p>
        <p><a class="btn" href="{{ route('login') }}">Go to login</a></p>
    @elseif ($status === 'wait')
        <h1>Please wait</h1>
        <p>Please wait until the 2-minute cooling period ends (about {{ $until }}), then try again.</p>
        <p><a class="btn" href="{{ $retryUrl }}">Try again</a></p>
    @else
        <h1>Link not valid</h1>
        <p>This unlock link is invalid or has expired.</p>
        <p><a class="btn" href="{{ route('login') }}">Go to login</a></p>
    @endif
</div>
@endsection