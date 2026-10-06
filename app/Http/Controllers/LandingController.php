<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = User::find($request->session()->get('user_id'));

        // The account was deleted while the session was still alive.
        if (! $user) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        // Assumption for "accessible user accounts": every registered user (safe columns only).
        // $accounts = User::orderBy('created_at')->get([
        
        // Assumption for "accessible user accounts": accounts that can log in, meaning a verified email
        // (a locked account stays in the list with its "Locked" label). Safe columns only.
        $accounts = User::whereNotNull('email_verified_at')->orderBy('created_at')->get([
            'id', 'first_name', 'middle_initial', 'last_name', 'email',
            'email_verified_at', 'mobile_verified', 'is_locked', 'created_at',
        ]);

        return view('landing', ['user' => $user, 'accounts' => $accounts]);
    }
}