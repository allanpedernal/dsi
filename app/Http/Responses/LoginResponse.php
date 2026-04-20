<?php

namespace App\Http\Responses;

use App\Support\AccessibleHome;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/**
 * Redirects the freshly-authenticated user to the first page their permissions allow.
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->intended(AccessibleHome::for($request->user()));
    }
}
