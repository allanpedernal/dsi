<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 * Base controller mixing in authorisation and validation helpers for subclasses.
 */
abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;
}
