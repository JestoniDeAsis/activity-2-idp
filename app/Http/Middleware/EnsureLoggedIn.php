<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLoggedIn
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('user_id')) {
            return redirect()->route('login');
        }

        $response = $next($request);

        // Stops the Back button from showing the page again after logout.
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}