<?php

namespace App\Http\Controllers;

use App\Support\AccessibleHome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Landing route for signed-in users without a permissioned dashboard.
 */
class HomeController extends Controller
{
    /** Redirect to the user's first accessible page, or render a minimal home fallback. */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        $destination = AccessibleHome::for($user);
        if ($destination !== '/home') {
            return redirect($destination);
        }

        return Inertia::render('home', [
            'name' => $user?->name,
        ]);
    }
}
