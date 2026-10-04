@extends('layouts.app')

@section('title', 'Register')

@section('content')
@php
    $selectedCountry = old('country', '');
    $dial = collect(config('countries'))->firstWhere('name', $selectedCountry)['dial'] ?? '+';
    $zipLookup = collect(config('location_api.countries'))->map(fn ($c) => $c['zip_lookup'])->all();
    $locationUrls = [
        'states' => route('locations.states', [], false),
        'cities' => route('locations.cities', [], false),
        'zips' => route('locations.zips', [], false),
    ];
    $oldLocation = [
        'state' => old('state'),
        'city' => old('city'),
        'zip_code' => old('zip_code'),
    ];
@endphp

<div class="card">
    <h1>Create your account</h1>

    <form id="register-form" method="POST" action="{{ route('register.store') }}" novalidate>
        @csrf

        <div class="grid">
            <div class="field">
                <label for="first_name">First name</label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" maxlength="50" autocomplete="given-name" required>
                <small class="error" data-error-for="first_name">{{ $errors->first('first_name') }}</small>
            </div>

            <div class="field">
                <label for="last_name">Last name</label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" maxlength="50" autocomplete="family-name" required>
                <small class="error" data-error-for="last_name">{{ $errors->first('last_name') }}</small>
            </div>

            <div class="field">
                <label for="middle_initial">Middle initial <span class="hint">(optional)</span></label>
                <input type="text" id="middle_initial" name="middle_initial" value="{{ old('middle_initial') }}" maxlength="2" placeholder="A or A.">
                <small class="error" data-error-for="middle_initial">{{ $errors->first('middle_initial') }}</small>
            </div>

            <div class="field">
                <label for="birthday">Birthday</label>
                <input type="text" id="birthday" name="birthday" value="{{ old('birthday') }}" maxlength="10" placeholder="MM/DD/YYYY" inputmode="numeric" autocomplete="off" required>
                <small class="error" data-error-for="birthday">{{ $errors->first('birthday') }}</small>
            </div>

            <div class="field full">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
                <small class="hint">Allowed: {{ implode(', ', config('email_domains.allowed')) }}</small>
                <small class="error" data-error-for="email">{{ $errors->first('email') }}</small>
            </div>

            <div class="field">
                <label for="country">Country</label>
                <select id="country" name="country" required>
                    <option value="" @selected($selectedCountry === '')>Select a country</option>
                    @foreach (config('countries') as $c)
                        <option value="{{ $c['name'] }}" @selected($selectedCountry === $c['name'])>{{ $c['name'] }} ({{ $c['dial'] }})</option>
                    @endforeach
                </select>
                <small class="error" data-error-for="country">{{ $errors->first('country') }}</small>
            </div>

            <div class="field">
                <label for="mobile_number">Mobile number</label>
                <div class="phone-group">
                    <span class="phone-prefix" id="dial_code">{{ $dial }}</span>
                    <input type="tel" id="mobile_number" name="mobile_number" value="{{ old('mobile_number') }}" maxlength="15" inputmode="numeric" autocomplete="off" placeholder="{{ $selectedCountry === '' ? 'Select a country first' : '' }}" @disabled($selectedCountry === '') required>
                </div>
                <small class="error" data-error-for="mobile_number">{{ $errors->first('mobile_number') }}</small>
            </div>

            <div class="field full">
                <label for="house_street">House &amp; street</label>
                <input type="text" id="house_street" name="house_street" value="{{ old('house_street') }}" maxlength="255" autocomplete="address-line1" required>
                <small class="error" data-error-for="house_street">{{ $errors->first('house_street') }}</small>
            </div>

            <div class="field">
                <label for="state">State / Province</label>
                <select id="state" name="state" disabled>
                    <option value="">Select a country first</option>
                </select>
                <input type="text" id="state_text" name="state" maxlength="100" autocomplete="address-level1" hidden disabled>
                <small class="hint" data-note-for="state" hidden></small>
                <small class="error" data-error-for="state">{{ $errors->first('state') }}</small>
            </div>

            <div class="field">
                <label for="city">City</label>
                <select id="city" name="city" disabled>
                    <option value="">Select a state / province first</option>
                </select>
                <input type="text" id="city_text" name="city" maxlength="100" autocomplete="address-level2" hidden disabled>
                <small class="hint" data-note-for="city" hidden></small>
                <small class="error" data-error-for="city">{{ $errors->first('city') }}</small>
            </div>

            <div class="field">
                <label for="zip_code">ZIP code</label>
                <select id="zip_code" name="zip_code" disabled>
                    <option value="">Select a city first</option>
                </select>
                <input type="text" id="zip_code_text" name="zip_code" maxlength="20" autocomplete="postal-code" hidden disabled>
                <small class="hint" data-note-for="zip_code" hidden></small>
                <small class="error" data-error-for="zip_code">{{ $errors->first('zip_code') }}</small>
            </div>

            <div class="field full">
                <label for="password">Password</label>
                <div class="password-group">
                    <input type="password" id="password" name="password" autocomplete="new-password" required>
                    <button type="button" class="toggle-password" data-toggle-for="password" aria-controls="password" aria-pressed="false">Show</button>
                </div>
                <small class="hint">At least 12 characters with an uppercase letter, a lowercase letter, a number, and a special character.</small>
                <small class="error" data-error-for="password">{{ $errors->first('password') }}</small>
                <button type="button" id="suggest-btn" class="btn btn-secondary">Suggest strong password</button>
                <div id="suggest-box" class="suggest-box" hidden>
                    Suggested password: <code id="suggest-value"></code>
                    <span class="hint">It is already filled in both password fields. Save it somewhere safe.</span>
                </div>
            </div>

            <div class="field full">
                <label for="password_confirmation">Confirm password</label>
                <div class="password-group">
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                    <button type="button" class="toggle-password" data-toggle-for="password_confirmation" aria-controls="password_confirmation" aria-pressed="false">Show</button>
                </div>
                <small class="error" data-error-for="password_confirmation">{{ $errors->first('password_confirmation') }}</small>
            </div>
        </div>

        <button type="submit" id="submit-btn" class="btn">Create account</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    window.APP_COUNTRIES = @json(config('countries'));
    window.APP_EMAIL_DOMAINS = @json(config('email_domains.allowed'));
    window.APP_ZIP_LOOKUP = @json($zipLookup);
    window.APP_LOCATION_URLS = @json($locationUrls);
    window.APP_OLD = @json($oldLocation);
</script>
<script src="{{ asset('js/register.js') }}"></script>
@endpush