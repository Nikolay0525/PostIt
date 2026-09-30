<?php

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

    'fields' => [
        'name' => 'Name',
        'date_of_birth' => 'Date of birth',
        'email' => 'Email',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
        'new_password' => 'New password',
        'new_password_confirmation' => 'Confirm new password',
        'remember' => 'Remember me',
    ],

    'login' => [
        'title' => 'Log in',
        'heading' => 'Log in to your account',
        'subtitle' => 'Welcome back — pick up where you left off.',
        'submit' => 'Log in',
        'submitting' => 'Logging in…',
        'no_account' => 'New to PostIt?',
        'register_link' => 'Create an account',
        'forgot_password' => 'Forgot your password?',
        'reset_link' => 'Reset it',
    ],

    'register' => [
        'title' => 'Register',
        'heading' => 'Create your account',
        'subtitle' => 'Join communities built around long-form writing and open discussion.',
        'submit' => 'Create account',
        'submitting' => 'Creating account…',
        'has_account' => 'Already have an account?',
        'login_link' => 'Log in',
    ],

    'forgot' => [
        'title' => 'Forgot password',
        'heading' => 'Reset your password',
        'subtitle' => 'Enter your email and we\'ll send you a link to reset your password.',
        'submit' => 'Send reset link',
        'submitting' => 'Sending link…',
        'remembered' => 'Remembered your password?',
        'login_link' => 'Log in',
    ],

    'reset' => [
        'title' => 'Reset password',
        'heading' => 'Choose a new password',
        'subtitle' => 'Make it something you haven\'t used here before.',
        'submit' => 'Change password',
        'submitting' => 'Changing password…',
    ],

    'verify' => [
        'title' => 'Verify email',
        'heading' => 'Verify your email',
        'subtitle' => 'We\'ve sent a verification link to your email address. Click the link to activate your account.',
        'link_sent' => 'A new verification link has been sent to your email address.',
        'resend_in' => 'Resend available in :time',
        'no_email' => 'Didn\'t get the email?',
        'resend' => 'Resend verification link',
    ],

];
