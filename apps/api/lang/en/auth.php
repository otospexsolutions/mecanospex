<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    // Custom authentication messages
    'unauthorized' => 'You are not authorized to perform this action.',
    'unauthenticated' => 'Please log in to continue.',
    'token_expired' => 'Your session has expired. Please log in again.',
    'token_invalid' => 'Invalid authentication token.',
    'account_disabled' => 'Your account has been disabled.',
    'email_not_verified' => 'Please verify your email address.',
    'logout_success' => 'You have been logged out successfully.',
    'login_success' => 'Login successful.',
];
