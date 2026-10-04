<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Home - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="landing-body">

<header class="navbar">
    <div class="navbar-inner">
        <a href="{{ route('landing') }}" class="navbar-logo">{{ config('app.name') }}</a>

        <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="nav-menu">
            <span></span><span></span><span></span>
        </button>

        <nav class="nav-menu" id="nav-menu">
            <ul class="nav-links">
                <li><a href="#" class="nav-link">Dashboard</a></li>
                <li><a href="#" class="nav-link">Profile</a></li>
                <li><a href="#" class="nav-link">Settings</a></li>
                <li><a href="#" class="nav-link" data-open-modal="holidays">Philippine Holidays</a></li>
            </ul>

            <div class="profile" id="profile">
                <button type="button" class="profile-btn" id="profile-btn" aria-haspopup="true" aria-expanded="false">
                    <span class="avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->first_name, 0, 1)) }}</span>
                    <span>{{ $user->first_name }}</span>
                </button>
                <div class="profile-menu" id="profile-menu" hidden>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="profile-menu-item">Log out</button>
                    </form>
                </div>
            </div>
        </nav>
    </div>
</header>

<main class="hero">
    <div class="hero-content">
        <p class="hero-eyebrow">Welcome back</p>
        <h1>Hello, {{ $user->first_name }}!</h1>
        <p class="hero-text">You are logged in. Open the panel below to see the registered accounts and the Philippine holiday calendar.</p>
        <div class="hero-actions">
            <button type="button" class="btn btn-lg" data-open-modal="accounts">View More</button>
        </div>
    </div>
</main>

<div class="modal" id="modal" hidden>
    <div class="modal-backdrop" data-close-modal></div>

    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="modal-header">
            <h2 id="modal-title">More</h2>
            <button type="button" class="modal-close" data-close-modal aria-label="Close">&times;</button>
        </div>

        <div class="tabs" role="tablist">
            <button type="button" class="tab active" role="tab" id="tab-accounts" data-tab="accounts" aria-controls="panel-accounts" aria-selected="true">Accounts</button>
            <button type="button" class="tab" role="tab" id="tab-holidays" data-tab="holidays" aria-controls="panel-holidays" aria-selected="false" tabindex="-1">Calendars / Holidays</button>
        </div>

        <div class="modal-body">
            <section class="tab-panel" role="tabpanel" id="panel-accounts" aria-labelledby="tab-accounts">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Email verified</th>
                                <th>Mobile verified</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($accounts as $account)
                                <tr>
                                    <td>
                                        {{ trim($account->first_name . ' ' . $account->middle_initial . ' ' . $account->last_name) }}
                                        @if ($account->id === $user->id)<span class="hint">(you)</span>@endif
                                    </td>
                                    <td>{{ $account->email }}</td>
                                    <td><span class="badge {{ $account->email_verified_at ? 'badge-yes' : 'badge-no' }}">{{ $account->email_verified_at ? 'Yes' : 'No' }}</span></td>
                                    <td><span class="badge {{ $account->mobile_verified ? 'badge-yes' : 'badge-no' }}">{{ $account->mobile_verified ? 'Yes' : 'No' }}</span></td>
                                    <td><span class="badge {{ $account->is_locked ? 'badge-no' : 'badge-yes' }}">{{ $account->is_locked ? 'Locked' : 'Active' }}</span></td>
                                    <td>{{ $account->created_at->setTimezone('Asia/Manila')->format('M d, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="tab-panel" role="tabpanel" id="panel-holidays" aria-labelledby="tab-holidays" hidden>
                <div id="holidays-root"
                     data-url="{{ url('/holidays') }}"
                     data-min-year="{{ config('holidays.min_year') }}"
                     data-max-year="{{ config('holidays.max_year') }}">
                    <p class="hint">Loading...</p>
                </div>
            </section>
        </div>
    </div>
</div>

<script src="{{ asset('js/landing.js') }}"></script>
<script src="{{ asset('js/holidays.js') }}"></script>
</body>
</html>