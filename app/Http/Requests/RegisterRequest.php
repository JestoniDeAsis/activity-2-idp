<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    private function selectedCountry(): ?array
    {
        $name = $this->input('country');

        return is_string($name)
            ? collect(config('countries'))->firstWhere('name', $name)
            : null;
    }

    public function rules(): array
    {
        $country = $this->selectedCountry();
        $allowedDomains = config('email_domains.allowed');

        return [
            'first_name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[A-Za-z\s\'\-]+$/'],
            'last_name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[A-Za-z\s\'\-]+$/'],
            'middle_initial' => ['nullable', 'string', 'max:2', 'regex:/^[A-Za-z]\.?$/'],

            'birthday' => [
                'bail', 'required', 'string',
                function ($attribute, $value, $fail) {
                    if (! preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $value, $m)) {
                        $fail('Use the format MM/DD/YYYY.');
                        return;
                    }
                    $month = (int) $m[1];
                    $day = (int) $m[2];
                    $year = (int) $m[3];
                    if ($year < 1900 || ! checkdate($month, $day, $year)) {
                        $fail('That is not a valid date.');
                        return;
                    }
                    if (Carbon::create($year, $month, $day)->startOfDay()->gt(Carbon::today()->subYears(13))) {
                        $fail('You must be at least 13 years old.');
                    }
                },
            ],

            'email' => [
                'bail', 'required', 'string', 'email', 'max:255',
                function ($attribute, $value, $fail) use ($allowedDomains) {
                    $domain = Str::lower(Str::after($value, '@'));
                    if (! in_array($domain, $allowedDomains, true)) {
                        $fail('Only public email providers are allowed: ' . implode(', ', $allowedDomains) . '.');
                    }
                },
                'unique:users,email',
            ],

            'country' => ['required', Rule::in(array_column(config('countries'), 'name'))],

            'mobile_number' => [
                'bail', 'required', 'string',
                function ($attribute, $value, $fail) use ($country) {
                    if ($country && ! preg_match('/' . $country['phone'] . '/', $value)) {
                        $fail($country['phone_hint']);
                    }
                },
            ],

            'house_street' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9\s.,#\/\'&()\-]+$/'],
            'state' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\p{M}0-9\s.,\'’\-()\/&]+$/u'],
            'city' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\p{M}0-9\s.,\'’\-()\/&]+$/u'],

            'zip_code' => [
                'bail', 'required', 'string', 'max:20',
                function ($attribute, $value, $fail) use ($country) {
                    if ($country && ! preg_match('/' . $country['zip'] . '/', $value)) {
                        $fail($country['zip_hint']);
                        return;
                    }

                    // Metro Manila (NCR): the ZIP must belong to the chosen city (config/ncr_zips.php).
                    if ($country && $country['name'] === 'Philippines'
                        && trim((string) $this->input('state')) === 'Metro Manila (NCR)') {
                        $cityZips = config('ncr_zips')[trim((string) $this->input('city'))] ?? null;

                        if ($cityZips === null) {
                            $fail('Please choose a city from the Metro Manila list.');
                        } elseif (! array_key_exists($value, $cityZips)) {
                            $fail('That ZIP code does not belong to the selected city.');
                        }
                    }
                },
            ],

            'password' => [
                'required', 'string', 'min:12',
                'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/', 'regex:/[^A-Za-z0-9]/',
            ],
            'password_confirmation' => ['required', 'same:password'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'first_name.min' => 'First name must be 2 to 50 characters.',
            'first_name.max' => 'First name must be 2 to 50 characters.',
            'first_name.regex' => 'First name can only have letters, spaces, hyphens, and apostrophes.',

            'last_name.required' => 'Last name is required.',
            'last_name.min' => 'Last name must be 2 to 50 characters.',
            'last_name.max' => 'Last name must be 2 to 50 characters.',
            'last_name.regex' => 'Last name can only have letters, spaces, hyphens, and apostrophes.',

            'middle_initial.max' => 'Middle initial must be one letter with an optional period (e.g., A or A.).',
            'middle_initial.regex' => 'Middle initial must be one letter with an optional period (e.g., A or A.).',

            'birthday.required' => 'Birthday is required.',

            'email.required' => 'Email is required.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'This email is already registered.',

            'country.required' => 'Please choose a country.',
            'country.in' => 'Please choose a country from the list.',

            'mobile_number.required' => 'Mobile number is required.',

            'house_street.required' => 'House and street is required.',
            'house_street.regex' => 'House and street has characters that are not allowed.',
            'city.required' => 'City is required.',
            'city.regex' => 'City has characters that are not allowed.',
            'state.required' => 'State is required.',
            'state.regex' => 'State has characters that are not allowed.',
            'zip_code.required' => 'ZIP code is required.',

            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 12 characters.',
            'password.regex' => 'Password must include an uppercase letter, a lowercase letter, a number, and a special character.',
            'password_confirmation.required' => 'Please confirm your password.',
            'password_confirmation.same' => 'The passwords do not match.',
        ];
    }
}