<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Access
    |--------------------------------------------------------------------------
    |
    | The only email address allowed to sign in to the Filament admin panel
    | at /admin. While this value is empty, nobody can access the panel.
    |
    */

    'email' => env('ADMIN_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Support Contact
    |--------------------------------------------------------------------------
    |
    | The address on the public page at /hapus-akun where people can ask to
    | delete their account without the app, as Google Play requires. While
    | this value is empty, the page only explains how to delete in the app.
    |
    */

    'support_email' => env('SUPPORT_EMAIL'),

];
