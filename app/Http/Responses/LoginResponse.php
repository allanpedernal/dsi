<?php

namespace App\Http\Responses;

use App\Support\AccessibleHome;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->intended(AccessibleHome::for($request->user()));
    }
}
